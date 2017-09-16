#! /usr/bin/env python

import json
import os
import urllib2


def main():
    update_sources = {
        'browser': {
            'trunk': [
                'http://hg.mozilla.org/mozilla-central/raw-file/default/browser/locales/all-locales'
            ],
            'beta': [
                'http://hg.mozilla.org/releases/mozilla-beta/raw-file/default/browser/locales/shipped-locales'
            ],
            'release': [
                'http://hg.mozilla.org/releases/mozilla-release/raw-file/default/browser/locales/shipped-locales'
            ]
        },
        'mail': {
            'trunk': [
                'http://hg.mozilla.org/comm-central/raw-file/default/mail/locales/all-locales'
            ],
            'beta': [
                'http://hg.mozilla.org/releases/comm-beta/raw-file/default/mail/locales/shipped-locales'
            ],
            'release': [
                'http://hg.mozilla.org/releases/comm-release/raw-file/default/mail/locales/shipped-locales'
            ]
        },
        'mobile': {
            'trunk': [
                'http://hg.mozilla.org/mozilla-central/raw-file/default/mobile/android/locales/maemo-locales',
                'http://hg.mozilla.org/mozilla-central/raw-file/default/mobile/android/locales/all-locales'
            ],
            'beta': [
                'http://hg.mozilla.org/releases/mozilla-beta/raw-file/default/mobile/android/locales/maemo-locales',
                'http://hg.mozilla.org/releases/mozilla-beta/raw-file/default/mobile/android/locales/all-locales'
            ],
            'release': [
                'http://hg.mozilla.org/releases/mozilla-release/raw-file/default/mobile/android/locales/maemo-locales',
                'http://hg.mozilla.org/releases/mozilla-release/raw-file/default/mobile/android/locales/all-locales'
            ]
        },
        'suite': {
            'trunk': [
                'http://hg.mozilla.org/comm-central/raw-file/default/suite/locales/all-locales'
            ],
            'beta': [
                'http://hg.mozilla.org/releases/comm-beta/raw-file/default/suite/locales/shipped-locales'
            ],
            'release': [
                'http://hg.mozilla.org/releases/comm-release/raw-file/default/suite/locales/shipped-locales'
            ]
        },
    }

    # Get absolute path of ../config from current script location (not current
    # folder)
    config_folder = os.path.abspath(
        os.path.join(os.path.dirname(__file__),
                     os.pardir,
                     'config'))

    supported_locales = {}
    for product, channels in update_sources.iteritems():
        supported_locales[product] = {}
        for channel_id, update_sources in channels.iteritems():
            channel_locales = []
            for update_source in update_sources:
                print 'Reading sources for {0}-{1} from {2}'.format(product, channel_id, update_source)
                response = urllib2.urlopen(update_source)
                for line in response:
                    locale = line.rstrip('\r\n')
                    if 'shipped-locales' in update_source:
                        # Remove platform from shipped-locales
                        for text in ['linux', 'osx', 'win32']:
                            locale = locale.replace(text, '')
                    locale = locale.strip()
                    if locale != '' and locale not in channel_locales:
                        channel_locales.append(locale)
            # Include en-US
            channel_locales.append('en-US')
            channel_locales.sort()
            supported_locales[product][channel_id] = channel_locales

    print "Storing JSON file in /config"
    file_name = os.path.join(config_folder, 'shipping_locales.json')
    f = open(file_name, 'w')
    f.write(json.dumps(supported_locales, indent=4, sort_keys=True))
    f.close()


if __name__ == '__main__':
    main()
