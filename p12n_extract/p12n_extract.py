#! /usr/bin/env python

# This script is designed to work inside a fully functional Transvision
# installation (https://github.com/mozfr/transvision)

import argparse
import base64
import collections
import glob
import hashlib
import io
import json
import os
import re
import sys
from time import strftime, localtime
from xml.dom import minidom

# Python 2/3 compatibility
try:
    from ConfigParser import SafeConfigParser
except ImportError:
    from configparser import SafeConfigParser

try:
    dict.iteritems
except AttributeError:
    # Python 3
    def iteritems(d):
        return iter(d.items())
else:
    # Python 2
    def iteritems(d):
        return d.iteritems()


def to_unicode(s):
    try:
        return unicode(s, 'utf-8')
    except NameError:
        return s


class ProductizationData():

    def __init__(self, install_path, script_config_folder):
        ''' Initialize object '''

        # Check if the path to store files exists. If it doesn't, create it
        web_p12n_folder = os.path.join(install_path, 'web', 'p12n')
        if not os.path.exists(web_p12n_folder):
            os.makedirs(web_p12n_folder)
        self.output_folder = web_p12n_folder

        # Create a dictionary that auto-generates keys when trying to set a
        # a value for a key that doesn't exist (no need to check for its
        # existence)
        def nested_dict(): return collections.defaultdict(nested_dict)

        # Data storage
        self.data = nested_dict()
        self.errors = nested_dict()
        self.hashes = nested_dict()
        self.shared_searchplugins = nested_dict()
        self.default_searchplugins = nested_dict()
        self.verbose_mode = False

        self.region_mappings = {
            'be': 'BY',
            'kk': 'KZ',
            'zh-CN': 'CN',
        }

        try:
            shipping_locales_list = os.path.join(
                script_config_folder, 'shipping_locales.json')
            with open(shipping_locales_list) as data_file:
                self.shipping_locales = json.load(data_file)
        except Exception as e:
            print('Error reading config/shipping_locales.json')
            print(e)

        self.data_folder = os.path.join(
            script_config_folder, os.pardir, 'data')

        # Initialize images with a default one
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
        self.resource_images = nested_dict()

    def set_verbose_mode(self):
        ''' Set verbose mode '''

        self.verbose_mode = True

    def activity_log(self, product, channel, message):
        ''' Print log '''

        if self.verbose_mode:
            print('[{}][{}] - {}'.format(
                product, channel, message))

    def extract_shared(self, path, product, channel):
        ''' Store in shared_searchplugins a list of searchplugins (*.xml) from
        a shared folder. It could be en-US or a special locales/searchplugins
        folder.
        '''

        # Store all XML files in self.shared_searchplugins
        try:
            for searchplugin in glob.glob(os.path.join(path, '*.xml')):
                searchplugin_noext = os.path.splitext(
                    os.path.basename(searchplugin))[0]
                if channel in self.shared_searchplugins[product]:
                    self.shared_searchplugins[product][
                        channel].append(searchplugin_noext)
                else:
                    self.shared_searchplugins[product][
                        channel] = [searchplugin_noext]
        except Exception as e:
            print(
                'Error: problem reading list of shared searchplugins from {}'.format(path))
            print(e)

    def extract_defaults(self, centralized_source, product, channel):
        ''' Store the default list of searchplugins '''
        if centralized_source != '' and os.path.isfile(centralized_source):
            # Use centralized JSON as data source
            try:
                with open(centralized_source) as data_file:
                    centralized_json = json.load(data_file)
                self.default_searchplugins[product][channel] = centralized_json[
                    'default']['visibleDefaultEngines']
            except Exception as e:
                print(e)

    def extract_shared_resource_images(self, images_path, product, channel):
        ''' Extract shared resource:// images for searchplugins '''
        if os.path.isdir(images_path):
            ext_mapping = {
                '.ico': 'x-icon',
                '.png': 'png'
            }
            try:
                for image in glob.glob(os.path.join(images_path, '*')):
                    filename = os.path.basename(image)
                    ext = os.path.splitext(filename)[1]

                    with open(image, 'rb') as f:
                        data = f.read()
                    image_data = 'data:image/{};base64,{}'.format(
                        ext_mapping.get(ext, ''), base64.b64encode(data).decode())

                    self.resource_images[product][channel][filename] = image_data
                    # Store image in image_list if not already available
                    if image not in self.images_list:
                        self.images_list.append(image_data)
            except Exception as e:
                print(e)

    def extract_searchplugins_product(self, centralized_source, search_path, product, locale, channel):
        ''' Extract information about searchplugings '''

        try:
            list_sp = []
            errors = []
            warnings = []

            if locale != 'shared':
                if centralized_source != '' and os.path.isfile(centralized_source):
                    # Use centralized JSON as data source
                    try:
                        with open(centralized_source) as data_file:
                            centralized_json = json.load(data_file)
                        # Only consider shipping locales
                        if locale in self.shipping_locales[product][channel]:
                            if locale in centralized_json['locales']:
                                # We have searchplugins defined
                                list_sp = centralized_json['locales'][locale][
                                    'default']['visibleDefaultEngines']
                            else:
                                # Fall back to default
                                list_sp = self.default_searchplugins[
                                    product][channel]
                                warnings.append(
                                    'locale is falling back to default searchplugins')
                    except Exception as e:
                        print(e)
                else:
                    # Read the list of searchplugins from list.txt
                    file_list = os.path.join(search_path, 'list.txt')
                    if os.path.isfile(file_list):
                        with open(file_list, 'r') as f:
                            list_sp = f.read().splitlines()
                            # Remove empty lines
                            list_sp = list(filter(bool, list_sp))
                # Check for duplicates
                if len(list_sp) != len(set(list_sp)):
                    # set(list_sp) removes duplicates. If I'm here, there are
                    # duplicated elements in list.txt, which is an error
                    duplicated_items = [
                        x for x, y in
                        collections.Counter(list_sp).items() if y > 1
                    ]
                    duplicated_items_str = ', '.join(duplicated_items)
                    errors.append('there are duplicated items ({}) in the list'.format(
                        duplicated_items_str))
            else:
                # For 'shared' I must analyze all XML files in the folder,
                # since some searchplugins are not used in en-US but by other
                # locales
                list_sp = self.shared_searchplugins[product][channel]

            if locale not in ['en-US', 'shared']:
                # Get a list of all files inside search_path
                for searchplugin in glob.glob(os.path.join(search_path, '*')):
                    filename = os.path.basename(searchplugin)
                    # Remove extension
                    filename_noext = os.path.splitext(filename)[0]
                    if filename_noext in self.shared_searchplugins[product][channel]:
                        # File exists but has the same name of an en-US
                        # searchplugin.
                        errors.append(
                            'file {} should not exist in the locale folder, same name of en-US searchplugin'.format(filename))
                    else:
                        if filename_noext not in list_sp and filename != 'list.txt':
                            # Extra file or unused searchplugin, should be
                            # removed
                            errors.append(
                                'file {} not expected'.format(filename))

            # For each searchplugin check if the file exists (localized version) or
            # not (using en-US version to extract data)
            for sp in list_sp:
                sp_file = os.path.join(search_path, '{}.xml'.format(sp))
                existing_file = os.path.isfile(sp_file)

                if sp in self.shared_searchplugins[product][channel] and existing_file and locale not in ['en-US', 'shared']:
                    # There's a problem: file exists but has the same name of an
                    # en-US searchplugin. This file will never be picked at build
                    # time, so let's analyze the shared one and use it for JSON,
                    # acting like the file doesn't exist, and printing an error
                    existing_file = False

                if existing_file:
                    try:
                        # Store md5 hash for this file. All files are very
                        # small, so I don't bother using a buffer
                        with open(sp_file, 'rb') as f:
                            file_content = f.read()
                        self.hashes['locales'][locale][product][channel][
                            '{}.xml'.format(sp)] = hashlib.md5(file_content).hexdigest()

                        searchplugin_info = '({}, {}, {}, {}.xml)'.format(
                            locale, product, channel, sp)
                        try:
                            xmldoc = minidom.parse(sp_file)
                        except Exception as e:
                            # Some searchplugins have preprocessing instructions
                            # (#define, #if), so they fail validation. In order to
                            # extract the information I need, I read the file,
                            # remove lines starting with # and parse that content
                            # instead of the original XML file
                            preprocessor = False
                            cleaned_sp_content = b''
                            with open(sp_file, 'r') as f:
                                for line in f.readlines():
                                    if re.match('^#', line):
                                        # Line starts with a #
                                        preprocessor = True
                                    else:
                                        # Line is OK, adding it to newspcontent
                                        cleaned_sp_content += line.encode()
                            if preprocessor:
                                warnings.append('searchplugin contains preprocessor instructions (e.g. #define, #if) that have been stripped in order to parse the XML {}'.format(
                                    searchplugin_info))
                                try:
                                    with io.BytesIO(cleaned_sp_content) as f:
                                        xmldoc = minidom.parse(f)
                                except Exception as e:
                                    print(e)
                                    errors.append(
                                        'error parsing XML {}'.format(searchplugin_info))
                            else:
                                # XML is broken
                                xmldoc = []
                                errors.append(
                                    'error parsing XML {} <code>{}</code>'.format(searchplugin_info, str(e)))

                        # Some searchplugins use the form <tag>, others
                        # <os:tag>
                        try:
                            node = xmldoc.getElementsByTagName('ShortName')
                            if len(node) == 0:
                                node = xmldoc.getElementsByTagName(
                                    'os:ShortName')
                            name = node[0].childNodes[0].nodeValue
                        except Exception as e:
                            errors.append(
                                'error extracting name {}'.format(searchplugin_info))
                            name = 'not available'

                        try:
                            node = xmldoc.getElementsByTagName('Description')
                            if len(node) == 0:
                                node = xmldoc.getElementsByTagName(
                                    'os:Description')
                            description = node[0].childNodes[0].nodeValue
                        except Exception as e:
                            # We don't really use description anywhere, and it's
                            # usually removed on mobile, so I don't print
                            # errors
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
                            errors.append(
                                'error extracting URL {}'.format(searchplugin_info))
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

                                # If searchplugin is using a resource:// image,
                                # make sure it's available in images_list
                                # before checking further
                                if image.startswith('resource://'):
                                    # resource:// image
                                    filename = os.path.basename(
                                        image.split('//', 1)[1])
                                    try:
                                        if filename in self.resource_images[product][channel]:
                                            image = self.resource_images[
                                                product][channel][filename]
                                        else:
                                            errors.append(
                                                'resource:// image not {} available {}'.format(image, searchplugin_info))
                                    except KeyError:
                                        # There are no resource:// images for
                                        # this product and channel
                                        errors.append(
                                            'There are no resource:// images available {}'.format(searchplugin_info))

                                if image in self.images_list:
                                    # Image already stored in the JSON
                                    # record. Store only the index
                                    images.append(
                                        self.images_list.index(image))
                                else:
                                    # Store image in images_list, get index
                                    # and store in json
                                    self.images_list.append(image)
                                    images.append(len(self.images_list) - 1)

                                # On mobile we can't have % characters, see for
                                # example bug 850984. Print a warning in this
                                # case
                                if product == 'mobile':
                                    if '%' in image:
                                        warnings.append('searchplugin\'s image on mobile can\'t contain % character {}'.format(
                                            searchplugin_info))
                        except Exception as e:
                            errors.append(
                                'error extracting image {}'.format(searchplugin_info))
                            # Use default empty image
                            images.append(0)

                        # No images in the searchplugin
                        if not images:
                            errors.append(
                                'no images available {}'.format(searchplugin_info))
                            # Use default empty image
                            images = [0]

                        self.data['locales'][locale][product][channel]['searchplugins'][sp] = {
                            'file': '{}.xml'.format(sp),
                            'name': name,
                            'description': description,
                            'url': url,
                            'secure': secure,
                            'images': images,
                        }
                    except Exception as e:
                        errors.append(
                            'error analyzing searchplugin {} <code>{}</code>'.format(searchplugin_info, str(e)))
                else:
                    # File does not exist, locale is using the same plugin
                    # available in the shared folder. I have to retrieve it
                    # from the existing dictionary
                    if sp in self.data['locales']['shared'][product][channel]['searchplugins']:
                        searchplugin_shared = self.data['locales'][
                            'shared'][product][channel]['searchplugins'][sp]

                        self.data['locales'][locale][product][channel]['searchplugins'][sp] = {
                            'file': '{}.xml'.format(sp),
                            'name': searchplugin_shared['name'],
                            'description': searchplugin_shared['description'],
                            'url': searchplugin_shared['url'],
                            'secure': searchplugin_shared['secure'],
                            'images': searchplugin_shared['images']
                        }
                    else:
                        # File does not exist but we don't have it in en-US either.
                        # This means that list.txt references a non existing
                        # plugin (luckily builds won't fail anymore).
                        # Starting from April 2016, we need to deal with :hidden
                        # searchplugins in l10n repos (don't report errors)
                        if not sp.endswith(':hidden'):
                            errors.append('file referenced in the list of searchplugins but not available ({}, {}, {}, {}.xml)'.format(
                                locale, product, channel, sp))

            # Save errors and warnings
            if errors:
                self.errors['locales'][locale][
                    product][channel]['errors'] = errors
            if warnings:
                self.errors['locales'][locale][product][
                    channel]['warnings'] = warnings
        except Exception as e:
            print('[{}] problem reading searchplugins'.format(locale))
            print(e)

    def extract_productization_product(self, centralized_source, region_file, product, locale, channel):
        ''' Extract productization data and check for errors '''

        # Extract p12n information from region.properties
        errors = []
        warnings = []

        def nested_dict(): return collections.defaultdict(nested_dict)

        try:
            available_searchplugins = []
            if channel in self.data['locales'][locale][product]:
                # I need to proceed only if I have searchplugins for this
                # branch+product+locale
                sp_record = self.data['locales'][locale][product][channel]['searchplugins']
                for sp_name, sp_data in iteritems(sp_record):
                    # Store the 'name' attribute of each searchplugin, used to
                    # validate search.order
                    if 'name' in sp_data:
                        available_searchplugins.append(sp_data['name'])

                # Initialize defaults
                default_engine_name = '-'
                search_order = nested_dict()
                feed_handlers = nested_dict()
                handler_version = '-'
                content_handlers = nested_dict()

                # Check if there is a centralized source, and it has information
                # on default engine
                central_default = False
                if centralized_source != '' and os.path.isfile(centralized_source):
                    try:
                        with open(centralized_source) as data_file:
                            centralized_json = json.load(data_file)

                        # Read DEFAULT ENGINE
                        # Start with the generic default
                        if 'searchDefault' in centralized_json['default']:
                            default_engine_name = centralized_json['default']['searchDefault']

                        # Check if there's a default for the locale
                        locale_data = centralized_json['locales'][locale]
                        if 'default' in locale_data and 'searchDefault' in locale_data['default']:
                            default_engine_name = locale_data['default']['searchDefault']

                        # As a last resort, use region override
                        locale_region = self.region_mappings.get(
                            locale, locale.upper())
                        if locale_region in locale_data and 'searchDefault' in locale_data[locale_region]:
                            default_engine_name = locale_data[locale_region]['searchDefault']

                        if default_engine_name != '-':
                            central_default = True

                        if default_engine_name not in available_searchplugins:
                            errors.append(u'{} is set as default but not available in searchplugins (check if the name is spelled correctly)'.format(
                                default_engine_name))

                        # Read SEARCH ORDER
                        # If default is defined in list.json, search order is
                        # defined too, no need to store another check.

                        # Start with the generic default
                        search_order_list = []
                        if 'searchOrder' in centralized_json['default']:
                            # 1st is always the default search engine
                            search_order_list.append(
                                centralized_json['default']['searchDefault'])
                            # Add other search engines
                            for engine_name in centralized_json['default']['searchOrder']:
                                search_order_list.append(engine_name)

                        # Check if search order is defined for the locale
                        if 'default' in locale_data and 'searchOrder' in locale_data['default']:
                            search_order_list = []
                            # 1st is always the default search engine. Use the value already determined
                            search_order_list.append(default_engine_name)
                            # Add other search engines
                            for engine_name in locale_data['default']['searchOrder']:
                                search_order_list.append(engine_name)

                        # Store the list
                        i = 1
                        for engine_name in search_order_list:
                            search_order[str(i)] = engine_name
                            i += 1
                            if engine_name not in available_searchplugins:
                                errors.append(
                                    '{} is defined in searchorder but not available in searchplugins (check if the name is spelled correctly)'.format(engine_name))
                    except Exception as e:
                        print(e)

                existing_file = os.path.isfile(region_file)
                if existing_file:
                    try:
                        # Store md5 hash for this file. All files are very
                        # small, so I don't bother using a buffer. 'suite' is
                        # a special case since it has 2 of them.
                        with open(region_file, 'rb') as f:
                            file_content = f.read()
                        index_name = os.path.basename(region_file)
                        if product == 'suite':
                            if 'common' in region_file:
                                index_name = 'common_{}'.format(index_name)
                            else:
                                index_name = 'browser_{}'.format(index_name)
                        self.hashes['locales'][locale][product][channel][
                            index_name] = hashlib.md5(file_content).hexdigest()

                        # Read region.properties, ignore comments and empty
                        # lines
                        settings = {}
                        with open(region_file) as f:
                            for line in f:
                                li = line.strip()
                                if not li.startswith('#') and li != '':
                                    try:
                                        # Split considering only the first =
                                        key, value = li.split('=', 1)
                                        # Remove whitespaces, some locales use key =
                                        # value instead of key=value
                                        settings[key.strip()] = value.strip()
                                    except Exception as e:
                                        errors.append('problem parsing {} ({}, {}, {})'.format(
                                            region_file, locale, product, channel))
                    except Exception as e:
                        print(e)
                        errors.append('problem reading {} ({}, {}, {})'.format(
                            region_file, locale, product, channel))

                    try:
                        for key, value in iteritems(settings):
                            line_ok = False

                            # Default search engine name. Example:
                            # browser.search.defaultenginename=Google
                            property_name = 'browser.search.defaultenginename'
                            if key.startswith(property_name):
                                if central_default:
                                    warnings.append(
                                        '{} is obsolete'.format(key))
                                else:
                                    line_ok = True
                                    default_engine_name = settings[property_name]
                                    if to_unicode(default_engine_name) not in available_searchplugins:
                                        pass
                                        errors.append('{} is set as default but not available in searchplugins (check if the name is spelled correctly)'.format(
                                            default_engine_name))

                            # Search engines order. Example:
                            # browser.search.order.1=Google
                            if key.startswith('browser.search.order.'):
                                if central_default:
                                    warnings.append(
                                        '{} is obsolete'.format(key))
                                else:
                                    line_ok = True
                                    search_order[key[-1:]] = value
                                    if to_unicode(value) not in available_searchplugins:
                                        if value != '':
                                            errors.append(
                                                '{} is defined in searchorder but not available in searchplugins (check if the name is spelled correctly)'.format(value))
                                        else:
                                            errors.append(
                                                '<code>{}</code> is empty'.format(key))

                            # Feed handlers. Example:
                            # browser.contentHandlers.types.0.title=My Yahoo!
                            # browser.contentHandlers.types.0.uri=http://add.my.yahoo.com/rss?url=%s
                            if key.startswith('browser.contentHandlers.types.'):
                                line_ok = True
                                if key.endswith('.title'):
                                    feed_handler_number = key[-7:-6]
                                    if feed_handler_number not in feed_handlers:
                                        feed_handlers[feed_handler_number] = {}
                                    feed_handlers[feed_handler_number][
                                        'title'] = value
                                    # Print warning for Google Reader
                                    if 'google' in value.lower():
                                        warnings.append(
                                            'Google Reader has been dismissed, see bug 882093 (<code>{}</code>)'.format(key))
                                if key.endswith('.uri'):
                                    feed_handler_number = key[-5:-4]
                                    feed_handlers[feed_handler_number][
                                        'uri'] = value

                            # Handler version. Example:
                            # gecko.handlerService.defaultHandlersVersion=4
                            property_name = 'gecko.handlerService.defaultHandlersVersion'
                            if key.startswith(property_name):
                                line_ok = True
                                handler_version = settings[property_name]

                            # Service handlers. Example:
                            # gecko.handlerService.schemes.webcal.0.name=30 Boxes
                            # gecko.handlerService.schemes.webcal.0.uriTemplate=https://30boxes.com/external/widget?refer=ff&url=%s
                            if key.startswith('gecko.handlerService.schemes.'):
                                line_ok = True
                                splitted_key = key.split('.')
                                ch_type = splitted_key[3]
                                ch_number = splitted_key[4]
                                ch_param = splitted_key[5]
                                if ch_param == 'name':
                                    content_handlers[ch_type][
                                        ch_number]['name'] = value
                                if ch_param == 'uriTemplate':
                                    content_handlers[ch_type][
                                        ch_number]['uri'] = value

                            # Ignore some keys for mail and seamonkey
                            if product == 'suite' or product == 'mail':
                                ignored_keys = [
                                    'app.update.url.details'
                                    'browser.search.defaulturl',
                                    'browser.startup.homepage',
                                    'browser.throbber.url',
                                    'browser.translation.',
                                    'browser.validate.html.service',
                                    'mail.addr_book.mapit_url.',
                                    'mailnews.localizedRe',
                                    'mailnews.messageid_browser.url',
                                    'startup.homepage_override_url',
                                ]
                                for ignored_key in ignored_keys:
                                    if key.startswith(ignored_key):
                                        line_ok = True

                            # Unrecognized line, print warning (not for en-US)
                            if not line_ok and locale != 'en-US':
                                warnings.append(
                                    'unknown key in region.properties <code>{}={}</code>'.format(key, value))
                    except Exception as e:
                        print('Error extracting data from region.properties (key: {}, {}, {}, {})'.format(
                            key, locale, product, channel))
                        print(available_searchplugins)
                        print(key)
                        print(e)
                else:
                    errors.append('file does not exist {} ({}, {}, {})'.format(
                        region_file, locale, product, channel))

            # Store data
            try:
                if product != 'suite':
                    self.data['locales'][locale][product][channel]['p12n'] = {
                        'defaultenginename': default_engine_name,
                        'searchorder': search_order,
                        'feedhandlers': feed_handlers,
                        'handlerversion': handler_version,
                        'contenthandlers': content_handlers
                    }
                else:
                    # Seamonkey has 2 different region.properties files:
                    # browser: has contenthandlers
                    # common: has search.order
                    # When analyzing common only update
                    # search.order and default
                    tmp_data = self.data['locales'][
                        locale][product][channel]['p12n']
                    if 'common' in region_file:
                        tmp_data[
                            'defaultenginename'] = default_engine_name
                        tmp_data['searchorder'] = search_order
                    else:
                        tmp_data = {
                            'defaultenginename': default_engine_name,
                            'searchorder': search_order,
                            'feedhandlers': feed_handlers,
                            'handlerversion': handler_version,
                            'contenthandlers': content_handlers
                        }
                    self.data['locales'][locale][product][
                        channel]['p12n'] = tmp_data
            except Exception as e:
                errors.append('problem saving data into JSON from {} ({}, {}, {})'.format(
                    region_file, locale, product, channel))

            # Save errors and warnings
            if errors:
                self.errors['locales'][locale][product][
                    channel]['p12n_errors'] = errors
            if warnings:
                self.errors['locales'][locale][product][
                    channel]['p12n_warnings'] = warnings
        except Exception as e:
            print('[{}] No searchplugins available for this locale ({})'.format(
                product, locale))
            print(e)

    def extract_p12n_channel(self, requested_product, channel_data, requested_channel, check_p12n):
        ''' Extract information from all products for this channel '''

        try:
            # Analyze en-US searchplugins first
            channel_folder = 'central' if requested_channel == 'trunk' else requested_channel
            base = os.path.join(self.data_folder, channel_folder)
            search_path_enUS = {
                'sp': {
                    'browser': os.path.join(base, 'browser', 'searchplugins'),
                    'mobile': os.path.join(base, 'mobile', 'searchplugins'),
                    'mail': os.path.join(base, 'mail', 'searchplugins'),
                    'suite': os.path.join(base, 'suite', 'searchplugins')
                },
                'p12n': {
                    'browser': [os.path.join(base, 'browser', 'browser-region', 'region.properties')],
                    'mobile': [os.path.join(base, 'mobile', 'browser-region', 'region.properties')],
                    'mail': [os.path.join(base, 'mail', 'browser-region', 'region.properties')],
                    'suite': [
                        os.path.join(base, 'suite', 'browser-region',
                                     'region-browser.properties'),
                        os.path.join(base, 'suite', 'browser-region',
                                     'region-common.properties')
                    ]
                }
            }
            for product in ['browser', 'mobile', 'mail', 'suite']:
                if requested_product in ['all', product]:
                    # Analyze en-US first
                    path_enUS = search_path_enUS['sp'][product]

                    # Define path to centralized list.json
                    path_centralized = ''
                    path_shared = path_enUS
                    if product in ['browser', 'mail', 'mobile']:
                        channel_folder = 'central' if requested_channel == 'trunk' else requested_channel
                        path_centralized = os.path.join(
                            self.data_folder, channel_folder, product, 'search', 'list.json')
                        path_shared = os.path.join(
                            self.data_folder, channel_folder, product, 'searchplugins')
                        if not os.path.isdir(path_shared):
                            # If the folder doesn't exist, fall back to en-US as source
                            # for shared searchplugins. This is needed while the
                            # centralization change rides the trains
                            self.activity_log(
                                product, requested_channel, 'Folder for shared searchplugins doesn\'t exist: {}'.format(path_shared))
                            path_shared = path_enUS

                    self.extract_shared(
                        path_shared, product, requested_channel)

                    if path_centralized != '':
                        self.extract_defaults(
                            path_centralized, product, requested_channel)

                    # Extract shared resource:// images from
                    # searchplugins/images
                    self.extract_shared_resource_images(
                        os.path.join(path_shared, 'images'),
                        product, requested_channel)

                    # Extract all shared searchplugins in a special 'shared'
                    # locale
                    self.extract_searchplugins_product(
                        path_centralized, path_shared, product, 'shared',
                        requested_channel)

                    # Extract searchplugins for en-US
                    self.extract_searchplugins_product(
                        path_centralized, path_shared, product, 'en-US',
                        requested_channel)

                    if check_p12n:
                        for path in search_path_enUS['p12n'][product]:
                            self.extract_productization_product(
                                path_centralized, path, product, 'en-US',
                                requested_channel)

                    # Analyze all other locales for this product
                    for locale in self.shipping_locales[product][requested_channel]:
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
                        self.extract_searchplugins_product(
                            path_centralized, search_path_l10n['sp'][product],
                            product, locale,
                            requested_channel)
                        if check_p12n:
                            for path in search_path_l10n['p12n'][product]:
                                self.extract_productization_product(
                                    path_centralized, path, product, locale,
                                    requested_channel)
        except Exception as e:
            print(e)

    def output_data(self, pretty_output):
        ''' Complete the JSON structure and output data to files '''

        # Add images to the JSON
        images_data = {}
        for index, value in enumerate(self.images_list):
            images_data[index] = value
        self.data['images'] = images_data

        # Save data on file
        metadata = {
            'creation_date': strftime('%Y-%m-%d %H:%M:%S', localtime())
        }
        data_mapping = {
            'errors': self.errors,
            'hashes': self.hashes,
            'searchplugins': self.data
        }
        for group in ['errors', 'hashes', 'searchplugins']:
            json_data = data_mapping[group]
            json_data['metadata'] = metadata

            # Remove the pseudo locale 'shared' from data before saving
            if 'shared' in json_data['locales']:
                del(json_data['locales']['shared'])

            file_name = os.path.join(self.output_folder, '{}.json'.format(group))
            f = open(file_name, 'w')
            if pretty_output:
                f.write(json.dumps(json_data, sort_keys=True, indent=4))
            else:
                f.write(json.dumps(json_data, sort_keys=True))
            f.close()


def main():
    # Parse command line options
    cl_parser = argparse.ArgumentParser()
    cl_parser.add_argument(
        'config_folder', help='Path to Transvision /config folder')
    cl_parser.add_argument('-p', '--product', help='Choose a specific product',
                           choices=['browser', 'mobile', 'mail', 'suite', 'all'], default='all')
    cl_parser.add_argument('-b', '--branch', help='Choose a specific branch',
                           choices=['release', 'beta', 'trunk', 'all'], default='all')
    cl_parser.add_argument('-n', '--noproductization',
                           help='Disable productization checks', action='store_false')
    cl_parser.add_argument('--pretty',
                           help='Generate pretty output', action='store_true')
    cl_parser.add_argument('--verbose',
                           help='Display verbose log', action='store_true')
    args = cl_parser.parse_args()

    # Read Transvision's configuration file by getting the absolute path of
    # ../config from current script location (not current folder). Store all
    # needed folders in vars.
    parser = SafeConfigParser()
    transvision_config = os.path.abspath(
        os.path.join(args.config_folder, 'config.ini'))
    if not os.path.isfile(transvision_config):
        print('config.ini not found in {}'.format(args.config_folder))
        sys.exit(1)
    parser.read(transvision_config)
    local_install = parser.get('config', 'install')
    local_hg = parser.get('config', 'local_hg')

    # Path to ../config, for the list of shipping locales
    script_config_folder = os.path.abspath(
        os.path.join(os.path.dirname(__file__),
                     os.pardir,
                     'config'))

    p12n = ProductizationData(local_install, script_config_folder)
    if args.verbose:
        p12n.set_verbose_mode()

    if args.branch == 'all':
        channels = ['release', 'beta', 'trunk']
    else:
        channels = [args.branch]

    for channel in channels:
        channel_data = {
            'l10n_path': os.path.join(local_hg, 'gecko_strings')
        }
        p12n.extract_p12n_channel(
            args.product, channel_data, channel, args.noproductization)
    p12n.output_data(args.pretty)


if __name__ == '__main__':
    main()
