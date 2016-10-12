# -*- coding: utf-8 -*-

import collections
import json
import os
import p12n_extract.p12n_extract
import shutil
import unittest


class TestSearchpluginAnalysis(unittest.TestCase):

    def setUp(self):
        nested_dict = lambda: collections.defaultdict(nested_dict)
        self.files_path = os.path.join(os.path.dirname(__file__), 'files')
        self.p12n = p12n_extract.p12n_extract.ProductizationData(
            self.files_path)

    def tearDown(self):
        del self.files_path
        del self.p12n

    @classmethod
    def tearDownClass(self):
        # Remove the temporary web/p12n folder
        files_folder = os.path.join(os.path.dirname(__file__), 'files', 'web')
        shutil.rmtree(files_folder)

    def testCheckInit(self):
        files_folder = os.path.join(
            os.path.dirname(__file__), 'files', 'web', 'p12n')

        # Check that the output folder is set and exist
        self.assertEqual(self.p12n.output_folder, files_folder)
        self.assertTrue(os.path.exists(files_folder))

        # Check default image list
        self.assertEqual(len(self.p12n.images_list), 1)

        # Check one dictionary, and that it's actually a nested dictionary
        self.p12n.data['test'] = ['a', 'b']
        self.assertEquals(self.p12n.data['test'], ['a', 'b'])

    def testListEnglishSearchplugins(self):
        search_path = os.path.join(self.files_path, 'en-US', 'searchplugins')

        self.p12n.extract_splist_enUS('', search_path, 'browser', 'aurora')
        self.assertEqual(len(self.p12n.enUS_searchplugins['browser']['aurora']), 2)
        self.assertIn('google', self.p12n.enUS_searchplugins['browser']['aurora'])
        self.assertIn('twitter', self.p12n.enUS_searchplugins['browser']['aurora'])

    def testExtractInfoSearchpluginEnglish(self):
        search_path = os.path.join(self.files_path, 'en-US', 'searchplugins')
        self.p12n.enUS_searchplugins = {
            'browser': {
                'aurora': ['google', 'twitter']
            }
        }

        self.p12n.extract_searchplugins_product(
            '', search_path, 'browser', 'en-US', 'aurora')

        # Check searchplugins data
        single_record = self.p12n.data['locales'][
            'en-US']['browser']['aurora']['searchplugins']
        self.assertEqual(single_record['google']['name'], 'Google')
        self.assertEqual(single_record['google'][
                         'description'], 'Google Search')
        self.assertEqual(single_record['google']['secure'], 1)
        self.assertEqual(single_record['google']['file'], 'google.xml')
        self.assertEqual(single_record['google'][
                         'url'], 'https://www.google.com/')
        self.assertEqual(len(single_record['twitter']['images']), 3)

        # Check number of extracted images
        self.assertEqual(len(self.p12n.images_list), 7)

        # Check errors (should be empty)
        self.assertEqual(len(self.p12n.errors), 0)

    def testExtractInfoSearchpluginAA(self):
        # Read en-US searchplugins
        search_path = os.path.join(self.files_path, 'en-US', 'searchplugins')
        self.p12n.enUS_searchplugins = {
            'browser': {
                'aurora': ['google', 'twitter']
            }
        }
        self.p12n.extract_searchplugins_product(
            '', search_path, 'browser', 'en-US', 'aurora')

        # Read searchplugins for locale 'aa'
        search_path = os.path.join(self.files_path, 'aa', 'searchplugins')
        self.p12n.extract_searchplugins_product(
            '', search_path, 'browser', 'aa', 'aurora')

        # Check searchplugin data
        single_record = self.p12n.data['locales'][
            'aa']['browser']['aurora']['searchplugins']

        # Name should fall back to English
        self.assertEqual(single_record['google']['name'], 'Google')
        # Description should include '(en-US)'
        self.assertEqual(single_record['twitter'][
                         'description'], '(en-US) Realtime Twitter Search')
        # Missing image should fall back to record 0
        self.assertEqual(single_record['wikipedia-it']['images'], [0])

        # Check errors
        single_record = self.p12n.errors['locales']['aa']['browser']['aurora']

        self.assertEqual(len(single_record['errors']), 9)
        self.assertEqual(len(single_record['warnings']), 1)

        self.assertIn(
            'there are duplicated items (google) in the list', single_record['errors'])
        self.assertIn('file extrafile.xml not expected',
                      single_record['errors'])
        self.assertIn(
            'file google.xml should not exist in the locale folder, same name of en-US searchplugin', single_record['errors'])
        self.assertIn(
            'error parsing XML (aa, browser, aurora, broken.xml) <code>mismatched tag: line 18, column 2</code>', single_record['errors'])
        self.assertIn(
            'no images available (aa, browser, aurora, wikipedia-it.xml)', single_record['errors'])
        self.assertIn(
            'file referenced in the list of searchplugins but not available (aa, browser, aurora, wikipedia-aa.xml)', single_record['errors'])
        self.assertIn(
            'searchplugin contains preprocessor instructions (e.g. #define, #if) that have been stripped in order to parse the XML (aa, browser, aurora, wikipedia-it.xml)', single_record['warnings'])

        # Check hashes
        single_record = self.p12n.hashes['locales']['aa']['browser']['aurora']
        self.assertEqual(len(single_record), 2)
        self.assertEqual(
            single_record['wikipedia-it.xml'], 'eb4fc9045394c3e2065d41a33c9fdd35')



    def testExtractP12nInfo(self):
        # Read searchplugins for locale 'bb'
        search_path = os.path.join(self.files_path, 'bb', 'searchplugins')
        self.p12n.enUS_searchplugins = {
            'browser': {
                'aurora': []
            }
        }
        self.p12n.extract_searchplugins_product(
            '', search_path, 'browser', 'bb', 'aurora')

        # Extract p12n data
        search_path = os.path.join(self.files_path, 'bb', 'region.properties')
        self.p12n.extract_productization_product(
            search_path, 'browser', 'bb', 'aurora')

        # Check searchplugin data
        single_record = self.p12n.data['locales'][
            'bb']['browser']['aurora']['p12n']

        # Default engine name
        self.assertEqual(single_record['defaultenginename'], 'Yahoo')

        # Search order
        self.assertEqual(len(single_record['searchorder']), 4)
        self.assertEqual(single_record['searchorder']['1'], 'Google')
        self.assertEqual(single_record['searchorder']['2'], 'Yahoo')
        self.assertEqual(single_record['searchorder']['3'], '')
        self.assertEqual(single_record['searchorder']['4'], '谷歌搜索')

        # Handler version
        self.assertEqual(single_record['handlerversion'], '4')

        # Feed handlers
        self.assertEqual(len(single_record['feedhandlers']), 2)
        self.assertEqual(single_record['feedhandlers'][
                         '0']['title'], 'Mio Yahoo!')
        self.assertEqual(single_record['feedhandlers']['0'][
                         'uri'], 'https://add.my.yahoo.com/rss?url=%s')
        self.assertEqual(single_record['feedhandlers']['1']['title'], 'òàù+è§')

        # Content handlers
        self.assertEqual(len(single_record['contenthandlers']), 4)
        self.assertEqual(single_record['contenthandlers'][
                         'irc']['0']['name'], 'Mibbit')
        self.assertEqual(single_record['contenthandlers'][
                         'mailto']['1']['name'], 'Gmail')

        # Check errors
        single_record = self.p12n.errors['locales']['bb']['browser']['aurora']

        self.assertEqual(len(single_record['p12n_errors']), 4)
        self.assertEqual(len(single_record['p12n_warnings']), 1)
        self.assertIn('Yahoo is set as default but not available in searchplugins (check if the name is spelled correctly)',
                      single_record['p12n_errors'])
        self.assertIn('Yahoo is defined in searchorder but not available in searchplugins (check if the name is spelled correctly)',
                      single_record['p12n_errors'])
        self.assertIn('<code>browser.search.order.3</code> is empty',
                      single_record['p12n_errors'])
        self.assertIn('谷歌搜索 is defined in searchorder but not available in searchplugins (check if the name is spelled correctly)',
                      single_record['p12n_errors'])
        self.assertIn('unknown key in region.properties <code>test.key=dummy</code>',
                      single_record['p12n_warnings'])

        # Check hashes
        single_record = self.p12n.hashes['locales']['bb']['browser']['aurora']
        self.assertEqual(len(single_record), 2)
        self.assertEqual(
            single_record['region.properties'], '97f6db5f07a911cc9b0969c5b8cf3114')

    def testOutputData(self):
        search_path = os.path.join(self.files_path, 'bb', 'searchplugins')
        self.p12n.enUS_searchplugins = {
            'browser': {
                'aurora': []
            }
        }
        self.p12n.extract_searchplugins_product(
            '', search_path, 'browser', 'bb', 'aurora')

        # Extract p12n data
        search_path = os.path.join(self.files_path, 'bb', 'region.properties')
        self.p12n.extract_productization_product(
            search_path, 'browser', 'bb', 'aurora')

        data_mapping = {
            'errors': self.p12n.errors,
            'hashes': self.p12n.hashes,
            'searchplugins': self.p12n.data
        }

        # Standard output
        self.p12n.output_data(False)
        for group in ['errors', 'hashes', 'searchplugins']:
            json_data = data_mapping[group]
            file_name = os.path.join(self.p12n.output_folder, group + '.json')
            cmp_output = open(file_name, 'r').read()
            self.assertEqual(cmp_output, json.dumps(json_data, sort_keys=True))

        # Pretty output
        self.p12n.output_data(True)
        for group in ['errors', 'hashes', 'searchplugins']:
            json_data = data_mapping[group]
            file_name = os.path.join(self.p12n.output_folder, group + '.json')
            cmp_output = open(file_name, 'r').read()
            self.assertEqual(cmp_output, json.dumps(
                json_data, sort_keys=True, indent=4))

if __name__ == '__main__':
    unittest.main()
