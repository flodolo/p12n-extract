#! /usr/bin/env python

import argparse
import difflib
import json
import sys


def main():
    cl_parser = argparse.ArgumentParser()
    cl_parser.add_argument('new_file', help='Path to new JSON file')
    cl_parser.add_argument('old_file', help='Path to old JSON file')
    args = cl_parser.parse_args()

    json_data_new = json.load(open(args.new_file))
    json_data_old = json.load(open(args.old_file))

    # Remove metadata, replace image references with actual data URIs and
    # remove the images section
    for json_data in [json_data_new, json_data_old]:
        for locale in json_data['locales']:
            for product in json_data['locales'][locale]:
                for channel in json_data['locales'][locale][product]:
                    for section in json_data['locales'][locale][product][channel]:
                        if section == 'searchplugins':
                            for searchplugin_name, searchplugin_data in json_data['locales'][locale][product][channel]['searchplugins'].iteritems():
                                images = searchplugin_data['images']
                                data = []
                                for image_index in images:
                                    data.append(json_data['images'][
                                                str(image_index)])
                                data.sort()
                                json_data['locales'][locale][product][channel][
                                    'searchplugins'][searchplugin_name]['images'] = data
        # Remove keys
        json_data.pop('metadata', None)
        json_data.pop('images', None)

    output_new = json.dumps(
        json_data_new, sort_keys=True, indent=4).splitlines(1)
    output_old = json.dumps(
        json_data_old, sort_keys=True, indent=4).splitlines(1)

    diff = difflib.context_diff(
        output_old, output_new, args.old_file, args.new_file)

    for line in diff:
        sys.stdout.write(line)


if __name__ == '__main__':
    main()
