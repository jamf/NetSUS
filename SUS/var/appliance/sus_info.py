#!/usr/bin/env python

from optparse import OptionParser
from plistlib import readPlist
from json import dumps


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
