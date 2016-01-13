#! /usr/bin/env python

# This script is designed to work in Transvision
# https://github.com/mozfr/transvision

import argparse
import collections
import glob
import json
import os
import re
import StringIO
import sys
from ConfigParser import SafeConfigParser
from time import strftime, localtime
from xml.dom import minidom


def extract_p12n_product(source, product, locale, channel,
                         json_data, json_errors):
    # Extract p12n information from region.properties.
    errors = []
    warnings = []
    nested_dict = lambda: collections.defaultdict(nested_dict)

    try:
        available_searchplugins = []
        if channel in json_data["locales"][locale][product]:
            # I need to proceed only if I have searchplugins for this
            # branch+product+locale
            for element in json_data["locales"][locale][product] \
                                    [channel]["searchplugins"].values():
                # Store the "name" attribute of each searchplugin, used to
                # validate search.order
                if "name" in element:
                    available_searchplugins.append(element["name"])

            existingfile = os.path.isfile(source)
            if existingfile:
                try:
                    # Read region.properties, ignore comments and empty lines
                    values = {}
                    for line in open(source):
                        li = line.strip()
                        if not li.startswith("#") and li != "":
                            try:
                                # Split considering only the first =
                                key, value = li.split("=", 1)
                                # Remove whitespaces, some locales use key =
                                # value instead of key=value
                                values[key.strip()] = value.strip()
                            except:
                                errors.append(
                                    "problem parsing %s (%s, %s, %s)"
                                    % (source, locale, product, channel)
                                )
                except:
                    errors.append(
                        "problem reading %s (%s, %s, %s)"
                        % (source, locale, product, channel)
                    )

                defaultenginename = "-"
                searchorder = nested_dict()
                feedhandlers = nested_dict()
                handlerversion = "-"
                contenthandlers = nested_dict()

                for key, value in values.iteritems():
                    lineok = False

                    # Default search engine name. Example:
                    # browser.search.defaultenginename=Google
                    property_name = "browser.search.defaultenginename"
                    if key.startswith(property_name):
                        lineok = True
                        defaultenginename = values[property_name]
                        if unicode(defaultenginename, "utf-8") not in \
                                available_searchplugins:
                            errors.append(
                                "%s is set as default but not available in "
                                "searchplugins (check if the name is spelled "
                                "correctly)" % defaultenginename
                            )

                    # Search engines order. Example:
                    # browser.search.order.1=Google
                    if key.startswith("browser.search.order."):
                        lineok = True
                        searchorder[key[-1:]] = value
                        if (unicode(value, "utf-8") not in
                                available_searchplugins):
                            if value != "":
                                errors.append(
                                    "%s is defined in searchorder but not "
                                    "available in searchplugins (check if the "
                                    "name is spelled correctly)" % value
                                )
                            else:
                                errors.append(
                                    "<code>%s</code> is empty" % key
                                )

                    # Feed handlers. Example:
                    # browser.contentHandlers.types.0.title=My Yahoo!
                    # browser.contentHandlers.types.0.uri=http://add.my.yahoo.com/rss?url=%s
                    if key.startswith("browser.contentHandlers.types."):
                        lineok = True
                        if key.endswith(".title"):
                            feedhandler_number = key[-7:-6]
                            if feedhandler_number not in feedhandlers:
                                feedhandlers[feedhandler_number] = {}
                            feedhandlers[feedhandler_number]["title"] = value
                            # Print warning for Google Reader
                            if "google" in value.lower():
                                warnings.append(
                                    "Google Reader has been dismissed, "
                                    "see bug 882093 (<code>%s</code>)"
                                    % key
                                )
                        if key.endswith(".uri"):
                            feedhandler_number = key[-5:-4]
                            feedhandlers[feedhandler_number]["uri"] = value

                    # Handler version. Example:
                    # gecko.handlerService.defaultHandlersVersion=4
                    property_name = "gecko.handlerService.defaultHandlersVersion"
                    if key.startswith(property_name):
                        lineok = True
                        handlerversion = values[property_name]

                    # Service handlers. Example:
                    # gecko.handlerService.schemes.webcal.0.name=30 Boxes
                    # gecko.handlerService.schemes.webcal.0.uriTemplate=https://30boxes.com/external/widget?refer=ff&url=%s
                    if key.startswith("gecko.handlerService.schemes."):
                        lineok = True
                        splittedkey = key.split(".")
                        ch_type = splittedkey[3]
                        ch_number = splittedkey[4]
                        ch_param = splittedkey[5]
                        if ch_param == "name":
                            contenthandlers[ch_type][ch_number]["name"] = value
                        if ch_param == "uriTemplate":
                            contenthandlers[ch_type][ch_number]["uri"] = value

                    # Ignore some keys for mail and seamonkey
                    if product == "suite" or product == "mail":
                        ignored_keys = [
                            "app.update.url.details"
                            "browser.search.defaulturl",
                            "browser.startup.homepage",
                            "browser.throbber.url",
                            "browser.translation.service",
                            "browser.translation.serviceDomain",
                            "browser.validate.html.service",
                            "mail.addr_book.mapit_url.format",
                            "mailnews.localizedRe",
                            "mailnews.messageid_browser.url",
                            "startup.homepage_override_url",
                        ]
                        if key in ignored_keys:
                            lineok = True

                    # Unrecognized line, print warning (not for en-US)
                    if not lineok and locale != "en-US":
                        warnings.append(
                            "unknown key in region.properties "
                            "<code>%s=%s</code>"
                            % (key, value)
                        )

                try:
                    if product != "suite":
                        json_data["locales"][locale][product] \
                                 [channel]["p12n"] = {
                            "defaultenginename": defaultenginename,
                            "searchorder": searchorder,
                            "feedhandlers": feedhandlers,
                            "handlerversion": handlerversion,
                            "contenthandlers": contenthandlers
                        }
                    else:
                        # Seamonkey has 2 different region.properties files:
                        # browser: has contenthandlers
                        # common: has search.order
                        # When analyzing common in ony update
                        # search.order and default
                        tmp_data = json_data["locales"][locale][product] \
                                            [channel]["p12n"]
                        if "/common/region.properties" in source:
                            tmp_data["defaultenginename"] = defaultenginename
                            tmp_data["searchorder"] = searchorder
                        else:
                            tmp_data = {
                                "defaultenginename": defaultenginename,
                                "searchorder": searchorder,
                                "feedhandlers": feedhandlers,
                                "handlerversion": handlerversion,
                                "contenthandlers": contenthandlers
                            }
                        json_data["locales"][locale][product] \
                                 [channel]["p12n"] = tmp_data
                except:
                    errors.append(
                        "problem saving data into json from %s (%s, %s, %s)"
                        % (source, locale, product, channel)
                    )
            else:
                errors.append(
                    "file does not exist %s (%s, %s, %s)"
                    % (source, locale, product, channel)
                )
        # Save errors and warnings
        if len(errors) > 0:
            json_errors["locales"][locale][product] \
                       [channel]["p12n_errors"] = errors
        if len(warnings) > 0:
            json_errors["locales"][locale][product] \
                       [channel]["p12n_warnings"] = warnings
    except:
        errors.append(
            "[%s] No searchplugins available for this locale"
            % product
        )


class ProductizationData():

    def __init__(self, install_path):
        '''Initialize object'''

        # Check if the path to store files exists
        web_p12n_folder = os.path.join(install_path, 'web', 'p12n')
        if not os.path.exists(web_p12n_folder):
            os.makedirs(web_p12n_folder)
        self.output_folder = web_p12n_folder

        nested_dict = lambda: collections.defaultdict(nested_dict)
        self.data = nested_dict()
        self.errors = nested_dict()
        self.images_list = [
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34A'
            'AAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEwAACxMBAJqcGAAAAV5JREFUSImt1k1K'
            'JEEQhuFHzzC40u7RpZ5CL+FP4yFEGdFzCPYFxOnxAiOCt3DWouhCd44ulG7aRVVBkZ2'
            'ZVa0dEBRkRL1f5E9F5Zy8rWAL61jDj3L8Gf9wjXPcNnAmbBkXGGHc4CMM0G0L38VbC3'
            'Dor+g1wQ+/AA59P1f5d+GV74Tw5ciyDHFSPlOgVM5/dOoCfyIvbpaxzYRIPWc7knNew'
            'VdMnpaTYIahSB1eWT9gjJQn67ihulAkFuslZnkIV5FATqQtfIxLeEwEUyJt4WPcw0cm'
            'ISfSBB/jfT5T3czsIVPBTJZomk3umew3OZG/cDQFvDqmbUV+wU/NH1oIiImEH9pQrV0'
            'MIsHthurqIrGcs7p6V9HPQ8BpAl7P6UdyXrAYzFAvA5rWkyfvYAbwvRS8sh1FP58W/J'
            'KrPLSOop+3+ekPFRu6FAPNNQh1FdeWDaxioRx/wo3i2vIbdynAJ3C4ViylVaDnAAAAA'
            'ElFTkSuQmCC'
        ]

    def __extract_splist_enUS(self, path, list_sp_enUS):
        ''' Store in list_sp_enUS a list of en-US searchplugins (*.xml) in paths '''
        try:
            for searchplugin in glob.glob(os.path.join(path, '*.xml')):
                searchplugin_noext = os.path.splitext(
                    os.path.basename(searchplugin))[0]
                list_sp_enUS.append(searchplugin_noext)
        except:
            print 'Error: problem reading list of en-US searchplugins from {0}'.format(pathsource)

    def __extract_searchplugins_product(self, search_path, product, locale, channel, list_sp_enUS):
        try:
            list_sp = []
            errors = []
            warnings = []

            if locale != 'en-US':
                # Read the list of searchplugins from list.txt
                file_list = os.path.join(search_path, 'list.txt')
                if os.path.isfile(file_list):
                    list_sp = open(file_list, 'r').read().splitlines()
                    # Remove empty lines
                    list_sp = filter(bool, list_sp)
                    # Check for duplicates
                    if len(list_sp) != len(set(list_sp)):
                        # set(list_sp) removes duplicates. If I'm here, there are
                        # duplicated elements in list.txt, which is an error
                        duplicated_items = [
                            x for x, y in
                            collections.Counter(list_sp).items() if y > 1
                        ]
                        duplicated_items_str = ', '.join(duplicated_items)
                        errors.append(u'there are duplicated items ({0}) in the list'.format(duplicated_items_str))
            else:
                # en-US is different from all other locales: I must analyze all
                # XML files in the folder, since some searchplugins are not used
                # in en-US but from other locales
                list_sp = list_sp_enUS

            if locale != 'en-US' and not errors:
                # Get a list of all files inside search_path
                for searchplugin in glob.glob(os.path.join(search_path, '*')):
                    filename = os.path.basename(searchplugin)
                    # Remove extension
                    filename_noext = os.path.splitext(filename)[0]
                    if filename_noext in list_sp_enUS:
                        # File exists but has the same name of an en-US
                        # searchplugin.
                        errors.append(u'file {0} should not exist in the locale folder, same name of en-US searchplugin'.format(filename))
                    else:
                        if filename_noext not in list_sp and filename != 'list.txt':
                            # Extra file or unused searchplugin, should be removed
                            errors.append(u'file {0} not in list.txt'.format(filename))

            # For each searchplugin check if the file exists (localized version) or
            # not (using en-US version to extract data)
            for sp in list_sp:
                sp_file = os.path.join(search_path, sp + '.xml')
                existing_file = os.path.isfile(sp_file)

                if sp in list_sp_enUS and existing_file and locale != 'en-US':
                    # There's a problem: file exists but has the same name of an
                    # en-US searchplugin. This file will never be picked at build
                    # time, so let's analyze en-US and use it for JSON, acting
                    # like the file doesn't exist, and printing an error
                    existing_file = False

                if existing_file:
                    try:
                        searchplugin_info = u'({0}, {1}, {2}, {3}.xml)'.format(locale, product, channel, sp)
                        try:
                            xmldoc = minidom.parse(sp_file)
                        except Exception as e:
                            # Some searchplugins have preprocessing instructions
                            # (#define, #if), so they fail validation. In order to
                            # extract the information I need, I read the file,
                            # remove lines starting with # and parse that content
                            # instead of the original XML file
                            preprocessor = False
                            cleaned_sp_content = ''
                            for line in open(sp_file, 'r').readlines():
                                if re.match('^#', line):
                                    # Line starts with a #
                                    preprocessor = True
                                else:
                                    # Line is OK, adding it to newspcontent
                                    cleaned_sp_content += line
                            if preprocessor:
                                warnings.append(u'searchplugin contains preprocessor instructions (e.g. #define, #if) that have been stripped in order to parse the XML {0}'.format(searchplugin_info))
                                try:
                                    xmldoc = minidom.parse(StringIO.StringIO(cleaned_sp_content))
                                except Exception as e:
                                    errors.append(u'error parsing XML {0}'.format(searchplugin_info))
                            else:
                                # XML is broken
                                xmldoc = []
                                errors.append(u'error parsing XML {0} <code>{1}</code>'.format(searchplugin_info, str(e)))

                        # Some searchplugins use the form <tag>, others <os:tag>
                        try:
                            node = xmldoc.getElementsByTagName('ShortName')
                            if len(node) == 0:
                                node = xmldoc.getElementsByTagName('os:ShortName')
                            name = node[0].childNodes[0].nodeValue
                        except Exception as e:
                            errors.append(u'error extracting name {0}'.format(searchplugin_info))
                            name = 'not available'

                        try:
                            node = xmldoc.getElementsByTagName('Description')
                            if len(node) == 0:
                                node = xmldoc.getElementsByTagName('os:Description')
                            description = node[0].childNodes[0].nodeValue
                        except Exception as e:
                            # We don't really use description anywhere, and it's
                            # usually removed on mobile, so I don't print errors
                            description = 'not available'

                        try:
                            # I can have more than one URL element, for example one
                            # for searches and one for suggestions
                            secure = 0

                            nodes = xmldoc.getElementsByTagName('Url')
                            if len(nodes) == 0:
                                nodes = xmldoc.getElementsByTagName('os:Url')
                            for node in nodes:
                                if node.attributes['type'].nodeValue == 'text/html':
                                    url = node.attributes['template'].nodeValue
                            p = re.compile('^https://')
                            if p.match(url):
                                secure = 1
                        except Exception as e:
                            errors.append(u'error extracting URL {0}'.format(searchplugin_info))
                            url = 'not available'

                        try:
                            # Since bug 900137, searchplugins can have multiple
                            # images
                            images = []
                            nodes = xmldoc.getElementsByTagName('Image')
                            if len(nodes) == 0:
                                nodes = xmldoc.getElementsByTagName('os:Image')
                            for node in nodes:
                                image = node.childNodes[0].nodeValue
                                if image in self.images_list:
                                    # Image already stored. In the JSON record. Store
                                    # only the index
                                    images.append(self.images_list.index(image))
                                else:
                                    # Store image in images_list, get index and
                                    # store in json
                                    self.images_list.append(image)
                                    images.append(len(self.images_list) - 1)

                                # On mobile we can't have % characters, see for
                                # example bug 850984. Print a warning in this case
                                if product == 'mobile':
                                    if '%' in image:
                                        warnings.append(u'searchplugin\'s image on mobile can\'t contain % character {0}'.format(searchplugin_info))
                        except Exception as e:
                            errors.append('error extracting image {0}'.format(searchplugin_info))
                            # Use default empty image
                            images.append(0)

                        # No images in the searchplugin
                        if not images:
                            errors.append('no images available {0}'.format(searchplugin_info))
                            # Use default empty image
                            images = [images_list[0]]

                        self.data['locales'][locale][product][channel]['searchplugins'][sp] = {
                            'file': u'{0}.xml'.format(sp),
                            'name': name,
                            'description': description,
                            'url': url,
                            'secure': secure,
                            'images': images,
                        }
                    except Exception as e:
                        print e
                        errors.append('error analyzing searchplugin {0} <code>{1}</code>'.format(searchplugin_info, str(e)))
                else:
                    # File does not exist, locale is using the same plugin of en-US,
                    # I have to retrieve it from the existing dictionary
                    if sp in self.data['locales']['en-US'][product][channel]['searchplugins']:
                        searchplugin_enUS = self.data['locales']['en-US'][product][channel]['searchplugins'][sp]

                        self.data['locales'][locale][product][channel]['searchplugins'][sp] = {
                            'file': u'{0}.xml'.format(sp),
                            'name': searchplugin_enUS['name'],
                            'description': u'(en-US) {0}'.format(searchplugin_enUS['description']),
                            'url': searchplugin_enUS['url'],
                            'secure': searchplugin_enUS['secure'],
                            'images': searchplugin_enUS['images']
                        }
                    else:
                        # File does not exist but we don't have in in en-US either.
                        # This means that list.txt references a non existing
                        # plugin, which will cause the build to fail
                        errors.append(u'file referenced in list.txt but not available ({0}, {1}, {2}, {3}.xml)'.format(locale, product, channel, sp))

            # Save errors and warnings
            if errors:
                self.errors['locales'][locale][product][channel]['errors'] = errors
            if warnings:
                self.errors['locales'][locale][product][channel]['warnings'] = warnings
        except Exception as e:
            print u'[{0}] problem reading {1}'.format(locale, file_list)

    def extract_p12n_channel(self, requested_product, channel_data, requested_channel, check_p12n):
        '''Extract information from all products for this channel'''
        try:
            # Analyze en-US searchplugins first
            base = os.path.join(channel_data['source_path'], 'COMMUN')
            search_path_enUS = {
                'sp': {
                    'browser': os.path.join(base, 'browser', 'locales', 'en-US', 'en-US', 'searchplugins'),
                    'mobile': os.path.join(base, 'mobile', 'locales', 'en-US', 'en-US', 'searchplugins'),
                    'mail': os.path.join(base, 'mail', 'locales', 'en-US', 'en-US', 'searchplugins'),
                    'suite': os.path.join(base, 'suite', 'locales', 'en-US', 'en-US', 'searchplugins')
                },
                'p12n': {
                    'browser': [os.path.join(base, 'browser', 'locales', 'en-US', 'en-US', 'chrome', 'browser-region', 'region.properties')],
                    'mobile': [os.path.join(base, 'mobile', 'locales', 'en-US', 'en-US', 'chrome', 'region.properties')],
                    'mail': [os.path.join(base, 'mail', 'locales', 'en-US', 'en-US', 'chrome', 'messenger-region', 'region.properties')],
                    'suite': [
                        os.path.join(base, 'suite', 'locales', 'en-US',
                                     'en-US', 'chrome', 'browser', 'region.properties'),
                        os.path.join(base, 'suite', 'locales', 'en-US',
                                     'en-US', 'chrome', 'common', 'region.properties')
                    ]
                }
            }
            list_sp_enUS = {}
            locales_list = open(
                channel_data['locales_file'], 'r').read().splitlines()
            for product in ['browser', 'mobile', 'mail', 'suite']:
                if requested_product in ['all', product]:
                    # Analyze en-US first
                    list_sp_enUS[product] = []
                    self.__extract_splist_enUS(search_path_enUS['sp'][
                                               product], list_sp_enUS[product])
                    self.__extract_searchplugins_product(
                        search_path_enUS['sp'][product], product, 'en-US',
                        requested_channel, list_sp_enUS[product])
                    if check_p12n:
                        for path in search_path_enUS['p12n'][product]:
                            extract_p12n_product(
                                path, product, 'en-US', requested_channel, self.data, self.errors)

                    # Analyze all other locales for this product
                    for locale in locales_list:
                        base = os.path.join(channel_data['l10n_path'], locale)
                        search_path_l10n = {
                            'sp': {
                                'browser': os.path.join(base, 'browser', 'searchplugins'),
                                'mobile': os.path.join(base, 'mobile', 'searchplugins'),
                                'mail': os.path.join(base, 'mail', 'searchplugins'),
                                'suite': os.path.join(base, 'suite', 'searchplugins')
                            },
                            'p12n': {
                                'browser': [os.path.join(base, 'browser', 'chrome', 'browser-region', 'region.properties')],
                                'mobile': [os.path.join(base, 'mobile', 'chrome', 'region.properties')],
                                'mail': [os.path.join(base, 'mail', 'chrome', 'messenger-region', 'region.properties')],
                                'suite': [
                                    os.path.join(
                                        base, 'suite', 'chrome', 'browser', 'region.properties'),
                                    os.path.join(
                                        base, 'suite', 'chrome', 'common', 'region.properties')
                                ]
                            }
                        }
                        self.__extract_searchplugins_product(
                            search_path_l10n['sp'][product], product, locale,
                            requested_channel, list_sp_enUS[product])
                        if check_p12n:
                            for path in search_path_l10n['p12n'][product]:
                                extract_p12n_product(
                                    path, product, locale, requested_channel, self.data, self.errors)
        except Exception as e:
            print e

    def output_data(self, pretty_output):
        '''Complete the JSON structure and output data to files'''

        # Add images to the JSON
        images_data = {}
        for index, value in enumerate(self.images_list):
            images_data[index] = value
        self.data['images'] = images_data

        creation_date = strftime('%Y-%m-%d %H:%M:%S', localtime())

        # Save searchplugins and other productization data
        self.data['metadata'] = {
            'creation_date': creation_date
        }
        f = open(os.path.join(self.output_folder, 'searchplugins.json'), 'w')
        if pretty_output:
            f.write(json.dumps(self.data, sort_keys=True, indent=4))
        else:
            f.write(json.dumps(self.data, sort_keys=True))
        f.close()

        # Save errors
        self.errors['metadata'] = {
            'creation_date': creation_date
        }
        f = open(os.path.join(self.output_folder, 'errors.json'), 'w')
        if pretty_output:
            f.write(json.dumps(self.errors, sort_keys=True, indent=4))
        else:
            f.write(json.dumps(self.errors, sort_keys=True))
        f.close()


def main():
    # Parse command line options
    cl_parser = argparse.ArgumentParser()
    cl_parser.add_argument('-p', '--product', help='Choose a specific product',
                           choices=['browser', 'mobile', 'mail', 'suite', 'all'], default='all')
    cl_parser.add_argument('-b', '--branch', help='Choose a specific branch',
                           choices=['release', 'beta', 'aurora', 'trunk', 'all'], default='all')
    cl_parser.add_argument('-n', '--noproductization',
                           help='Disable productization checks', action='store_false')
    cl_parser.add_argument('--pretty',
                           help='Generate pretty output', action='store_true')
    args = cl_parser.parse_args()

    # Read Transvision's configuration file by getting the absolute path of
    # ../config from current script location (not current folder). Store all
    # needed folders in vars.
    parser = SafeConfigParser()
    config_folder = os.path.abspath(os.path.join(
        os.path.dirname(__file__), os.pardir, 'config'))
    parser.read(os.path.join(config_folder, 'config.ini'))
    local_install = parser.get('config', 'install')
    local_hg = parser.get('config', 'local_hg')
    config_files = os.path.join(parser.get('config', 'config'), 'sources')

    p12n = ProductizationData(local_install)
    for channel in ['release', 'beta', 'aurora', 'trunk']:
        if args.branch in ['all', channel]:
            source_name = 'central.txt' if channel == 'trunk' else '{0}.txt'.format(
                channel)
            channel_data = {
                'l10n_path': os.path.join(local_hg, '{0}_L10N'.format(channel.upper())),
                'locales_file': os.path.join(config_files, source_name),
                'source_path': os.path.join(local_hg, '{0}_EN-US'.format(channel.upper())),
            }
            p12n.extract_p12n_channel(
                args.product, channel_data, channel, args.noproductization)
    p12n.output_data(args.pretty)


if __name__ == '__main__':
    main()
