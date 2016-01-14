# -*- coding: utf-8 -*-

import collections
import json
import os
import p12n_extract.p12n_extract
import unittest


class TestSearchpluginAnalysis(unittest.TestCase):

    def setUp(self):
        nested_dict = lambda: collections.defaultdict(nested_dict)
        self.file_path = os.path.join(os.path.dirname(__file__), 'files')
        self.p12n = p12n_extract.p12n_extract.ProductizationData('')

    def tearDown(self):
        del self.file_path
        del self.p12n

    def testListEnglishSearchplugins(self):
        search_path = os.path.join(self.file_path, 'en-US', 'searchplugins')
        sp_list = []

        self.p12n.extract_splist_enUS(search_path, sp_list)
        self.assertEqual(sp_list, ['google', 'twitter'])

    def testExtractInfoSearchpluginEnglish(self):
        search_path = os.path.join(self.file_path, 'en-US', 'searchplugins')
        list_sp_enUS = ['google', 'twitter']

        self.p12n.extract_searchplugins_product(
            search_path, 'browser', 'en-US', 'aurora', list_sp_enUS)

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
        search_path = os.path.join(self.file_path, 'en-US', 'searchplugins')
        list_sp_enUS = ['google', 'twitter']

        self.p12n.extract_searchplugins_product(
            search_path, 'browser', 'en-US', 'aurora', list_sp_enUS)

        # Read searchplugins for locale 'aa'
        search_path = os.path.join(self.file_path, 'aa', 'searchplugins')

        self.p12n.extract_searchplugins_product(
            search_path, 'browser', 'aa', 'aurora', list_sp_enUS)

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
        self.assertIn('file extrafile.xml not in list.txt',
                      single_record['errors'])
        self.assertIn(
            'file google.xml should not exist in the locale folder, same name of en-US searchplugin', single_record['errors'])
        self.assertIn(
            'error parsing XML (aa, browser, aurora, broken.xml) <code>mismatched tag: line 18, column 2</code>', single_record['errors'])
        self.assertIn(
            'no images available (aa, browser, aurora, wikipedia-it.xml)', single_record['errors'])
        self.assertIn(
            'file referenced in list.txt but not available (aa, browser, aurora, wikipedia-aa.xml)', single_record['errors'])
        self.assertIn(
            'searchplugin contains preprocessor instructions (e.g. #define, #if) that have been stripped in order to parse the XML (aa, browser, aurora, wikipedia-it.xml)', single_record['warnings'])

        # Check hashes
        single_record = self.p12n.hashes['locales']['aa']['browser']['aurora']
        self.assertEqual(len(single_record), 2)
        self.assertEqual(
            single_record['wikipedia-it.xml'], 'eb4fc9045394c3e2065d41a33c9fdd35')

    def testExtractP12nInfo(self):
        # Read searchplugins for locale 'bb'
        search_path = os.path.join(self.file_path, 'bb', 'searchplugins')
        self.p12n.extract_searchplugins_product(
            search_path, 'browser', 'bb', 'aurora', [])

        # Extract p12n data
        search_path = os.path.join(self.file_path, 'bb', 'region.properties')

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


if __name__ == '__main__':
    unittest.main()
