#! /usr/bin/env python

import argparse
import json


def main():
    cl_parser = argparse.ArgumentParser()
    cl_parser.add_argument('new_file', help='Path to new JSON file')
    cl_parser.add_argument('old_file', help='Path to old JSON file')
    args = cl_parser.parse_args()

    json_data_new = json.load(open(args.new_file))
    json_data_old = json.load(open(args.old_file))

    locales = json_data_new['locales'].keys()
    locales.sort()

    for locale in locales:
        if 'browser' in json_data_new['locales'][locale]:
            new = json_data_new['locales'][locale]['browser']['trunk']['p12n']['searchorder']
            old = json_data_old['locales'][locale]['browser']['trunk']['p12n']['searchorder']

            for order, engine in old.iteritems():
                if order not in new:
                    print('Locale: {}'.format(locale))
                    print('Search order {} is missing'.format(order))
                elif new[order] != engine:
                    print('Locale: {}'.format(locale))
                    print(u'Old {}: {}'.format(order, engine))
                    print(u'New {}: {}'.format(order, new[order]))


if __name__ == '__main__':
    main()
