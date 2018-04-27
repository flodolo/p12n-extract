#! /usr/bin/env python

import json
import urllib2


json_file = 'searchplugins.json'
json_data = json.load(open(json_file))

products = ['browser', 'mobile']
errors = []

locales = []
for locale in json_data['locales']:
    locales.append(locale)
locales.sort()

for locale in locales:
    print('\n\n------\nAnalyzing {}...'.format(locale))
    for product in products:
        if product in json_data['locales'][locale] \
                and 'central' in json_data['locales'][locale][product]:
            searchplugins = json_data['locales'][locale][
                product]['central']['searchplugins']
            for searchplugin in searchplugins:
                if '(en-US)' not in searchplugins[searchplugin]['description']:
                    print('Checking {}'.format(searchplugins[searchplugin]['file']))
                    url = searchplugins[searchplugin]['url']
                    try:
                        response = urllib2.urlopen(url.encode('UTF-8'))
                    except urllib2.HTTPError as e:
                        errors.append(
                            '[{} - {}] Response: {} - URL: {}'.format(
                                locale, product, e.code, url))
                    except urllib2.URLError as e:
                        errors.append(
                            '[{} - {}] Response: {} - URL: {}'.format(
                                locale, product, e.reason, url))
                    except Exception as e:
                        errors.append(
                            '[{} - {}] Response: {} - URL: %s'.format(
                                locale, product, e.code, url))

if errors:
    print('\n'.join(errors))
