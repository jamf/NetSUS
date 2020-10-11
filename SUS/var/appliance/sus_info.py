#!/usr/bin/env python

from optparse import OptionParser
from plistlib import readPlist
from json import dumps

apple_catalog_version_map = {
	'index-10.15-10.14-10.13-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog': '10.15',
	'index-10.14-10.13-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog': '10.14',
	'index-10.13-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog': '10.13',
	'index-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog': '10.12',
	'index-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog': '10.11',
	'index-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog': '10.10',
	'index-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog': '10.9',
	'index-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog': '10.8',
	'index-lion-snowleopard-leopard.merged-1.sucatalog': '10.7',
	'index-leopard-snowleopard.merged-1.sucatalog': '10.6',
	'index-leopard.merged-1.sucatalog': '10.5',
	'index-1.sucatalog': '10.4',
	'index.sucatalog': '10.4',
}

def versions_from_catalogs(cats):
	versions = []
	for cat in cats:
		# End of URL
		short_cat = cat.split('/')[-1]
		if short_cat in apple_catalog_version_map.keys():
			versions.append(apple_catalog_version_map[short_cat])
	return versions
	

def list_products():
	try:
		product_info = readPlist('/srv/SUS/metadata/ProductInfo.plist')
	except:
		product_info = {}

	try:
		catalog_branches = readPlist('/srv/SUS/metadata/CatalogBranches.plist')
	except:
		catalog_branches = {}

	product_list = []

	for key in product_info.keys():
		if 'title' in product_info[key] and 'version' in product_info[key] and 'PostDate' in product_info[key]:

			post_date = product_info[key]['PostDate'].strftime('%Y-%m-%d %H:%M:%S')

			if not catalog_branches:
				branch_list = ''
			else:
				branch_list = [branch for branch in catalog_branches.keys()
							  if key in catalog_branches[branch]]
				branch_list.sort()

			deprecated = ''
			if len(product_info[key].get('AppleCatalogs', [])) < 1:
				deprecated = '(Deprecated)'

			product = {
				'id': key,
				'title': product_info[key]['title'],
				'version': product_info[key]['version'],
				'oscatalogs': versions_from_catalogs(product_info[key]['OriginalAppleCatalogs']),
				'PostDate': post_date,
				'BranchList': branch_list,
				'Deprecated': deprecated
			}

			product_list.append(product)

	product_list.sort()

	print dumps(product_list)


def product_detail(key):
	try:
		product_info = readPlist('/srv/SUS/metadata/ProductInfo.plist')
	except:
		product_info = {}

	if key in product_info:

		product_dict = product_info[key]

		bytes = int(product_dict['size'])

		units = [(" KB", 10**6), (" MB", 10**9), (" GB", 10**12), (" TB", 10**15)]
		for suffix, limit in units:
			if bytes > limit:
				continue
			else:
				size = str(round(bytes/float(limit/2**10), 1)) + suffix
				break

		post_date = product_dict['PostDate'].strftime('%Y-%m-%dT%H:%M:%SZ')

		deprecated = ''
		if len(product_dict.get('AppleCatalogs', [])) < 1:
			deprecated = '(Deprecated)'

		product = {
			'id': key,
			'title': product_dict['title'],
			'version': product_dict['version'],
			'size': size,
			'PostDate': post_date,
			'description': product_dict['description'],
			'oscatalogs': versions_from_catalogs(product_dict['OriginalAppleCatalogs']),
			'packages': product_dict['CatalogEntry']['Packages'],
			'Deprecated': deprecated
		}

		print dumps(product)


def main():
    parser = OptionParser()
    parser.add_option('--products', action='store_true', dest='products')
    parser.add_option('--details', metavar='PRODUCT_ID', dest='details')

    options, arguments = parser.parse_args()

    if options.products:
        list_products()
    if options.details:
        product_detail(options.details)

if __name__ == '__main__':
    main()
