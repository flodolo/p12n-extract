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
            self.files_path, self.files_path)

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

    def testExtractSharedResourceImages(self):
        images_path = os.path.join(self.files_path, 'images')
        self.p12n.extract_shared_resource_images(
            images_path, 'browser', 'aurora')

        self.assertEqual(
            len(self.p12n.resource_images['browser']['aurora']), 1)
        self.assertIn('wikipedia.ico', self.p12n.resource_images[
                      'browser']['aurora'])

        image_data = 'data:image/x-icon;base64,AAABAAIAICAQAAEABADoAgAAJgAAABAQEAABAAQAKAEAAA4DAAAoAAAAIAAAAEAAAAABAAQAAAAAAIACAAAAAAAAAAAAABAAAAAAAAAAAQEBABYWFgAnJycANTU1AEdHRwBZWVkAZWVlAHh4eACIiIgAmZmZAK6urgDMzMwA19fXAOnp6QD+/v4AAAAAAP//7u7u7u7u7u7u7u7u////7u7u7u7u7u7u7u7u7u7//u7u7u7u7u7u7u7u7u7u7/7u7u7u7u7u7u7u7u7u7u/u7u7u7u7u7u7u7u7u7u7u7u7u7u7X3u7u7I7u7u7u7u7u7u7uYF7u7uIK7u7u7u7u7u7u7QAM7u6vBO7u7u7u7u7u7ucABe7uMA/O7u7u7u7u7u7R8q/O6gCEbu7u7u7u7u7ukAnibuTx6g3u7u7u7u7u7hAe6gzP+O4Y7u7u7u7u7urwju4mXx7uge7u7u7u7u7jAd7uoACO7tCe7u7u7u7uoPfu7uEB3u7mPu7u7u7u7k8N7u7QBu7u6wru7u7u7uwAXu7ufwbu7u407u7u7u7lAM7u7RBQzu7ur87u7u7u0ATu7ucA0l7u7uFu7u7u7n/67u7RB+oL7u7nHe7u7u0fPu7ucA3uJO7u7Qju7u7o/67u7Q9u7q+u7u5R3u7u0Q/e7ub/vu7PLO7uX13u4w//Be4v/xnoH/+ekv//Xu7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7+7u7u7u7u7u7u7u7u7u7v/u7u7u7u7u7u7u7u7u7u7//u7u7u7u7u7u7u7u7u7v///+7u7u7u7u7u7u7u7v//8AAAD8AAAAOAAAABgAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAGAAAABwAAAA/AAAA8oAAAAEAAAACAAAAABAAQAAAAAAMAAAAAAAAAAAAAAABAAAAAAAAAAAQEBABcXFwAnJycAOzs7AElJSQBpaWkAeXl5AIaGhgCVlZUApqamALOzswDMzMwA2dnZAObm5gD+/v4AAAAAAP/u7u7u7u7//u7u7u7u7u/u7uzu7t7u7u7u4Y7lTu7u7u6QTtA77u7u7iaoctXu7u7qDOQZ5d7u7uRO5R7rbu7uv77iLu5O7u5D7pGn7pju7QrtKOTe4+6z+OT40z2RTO7u7u7u7u7u7u7u7u7u7u7+7u7u7u7u7//u7u7u7u7/wAMAAIABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACAAQAAwAMAAA=='
        self.assertEqual(self.p12n.resource_images['browser'][
                         'aurora']['wikipedia.ico'], image_data)

        # Check that image_list has been updated
        self.assertEqual(len(self.p12n.images_list), 2)
        self.assertIn(image_data, self.p12n.images_list)

    def testListEnglishSearchplugins(self):
        search_path = os.path.join(self.files_path, 'en-US', 'searchplugins')

        self.p12n.extract_shared(search_path, 'browser', 'aurora')
        self.p12n.extract_defaults('', 'browser', 'aurora')
        self.assertEqual(
            len(self.p12n.shared_searchplugins['browser']['aurora']), 2)
        self.assertIn('google', self.p12n.shared_searchplugins[
                      'browser']['aurora'])
        self.assertIn('twitter', self.p12n.shared_searchplugins[
                      'browser']['aurora'])

    def testExtractInfoSearchpluginEnglish(self):
        search_path = os.path.join(self.files_path, 'en-US', 'searchplugins')
        self.p12n.shared_searchplugins = {
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
        self.p12n.shared_searchplugins = {
            'browser': {
                'aurora': ['google', 'twitter']
            }
        }
        self.p12n.extract_searchplugins_product(
            '', search_path, 'browser', 'shared', 'aurora')
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

    def testCentralizedListEnglishSearchplugins(self):
        search_path = os.path.join(self.files_path, 'en-US', 'searchplugins')
        centralized_source = os.path.join(self.files_path, 'list.json')

        self.p12n.extract_shared(search_path, 'browser', 'aurora')
        self.p12n.extract_defaults(centralized_source, 'browser', 'aurora')

        self.assertEqual(
            len(self.p12n.shared_searchplugins['browser']['aurora']), 2)
        self.assertIn('google', self.p12n.shared_searchplugins[
                      'browser']['aurora'])
        self.assertIn('twitter', self.p12n.shared_searchplugins[
                      'browser']['aurora'])

        self.assertEqual(
            len(self.p12n.default_searchplugins['browser']['aurora']), 7)
        self.assertIn('ddg', self.p12n.default_searchplugins[
                      'browser']['aurora'])
        self.assertNotIn('ddg', self.p12n.default_searchplugins['test'])

    def testCentralizedExtractInfoSearchpluginEnglish(self):
        search_path = os.path.join(self.files_path, 'en-US', 'searchplugins')
        centralized_source = os.path.join(self.files_path, 'list.json')
        self.p12n.shared_searchplugins = {
            'browser': {
                'aurora': ['google', 'twitter']
            }
        }

        self.p12n.extract_searchplugins_product(
            centralized_source, search_path, 'browser', 'en-US', 'aurora')

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

    def testCentralizedExtractInfoSearchpluginMissing(self):
        # Read en-US searchplugins
        search_path = os.path.join(self.files_path, 'en-US', 'searchplugins')
        centralized_source = os.path.join(self.files_path, 'list.json')

        self.p12n.extract_shared(search_path, 'browser', 'aurora')
        self.p12n.extract_defaults(centralized_source, 'browser', 'aurora')

        self.p12n.extract_searchplugins_product(
            centralized_source, search_path, 'browser', 'shared', 'aurora')
        self.p12n.extract_searchplugins_product(
            centralized_source, search_path, 'browser', 'en-US', 'aurora')

        # Read searchplugins for locale 'test' (not available, but shipping)
        search_path = os.path.join(self.files_path, 'test', 'searchplugins')
        self.p12n.extract_searchplugins_product(
            centralized_source, search_path, 'browser', 'test', 'aurora')

        # Check searchplugin data
        single_record = self.p12n.data['locales'][
            'test']['browser']['aurora']['searchplugins']
        self.assertEqual(len(single_record), 2)

        # Check warning
        single_record = self.p12n.errors[
            'locales']['test']['browser']['aurora']
        self.assertEqual(len(single_record['errors']), 5)
        self.assertEqual(len(single_record['warnings']), 1)
        self.assertIn(
            'locale is falling back to default searchplugins', single_record['warnings'])

        # Read searchplugins for locale 'test2' (not available, not shipping)
        self.p12n.extract_searchplugins_product(
            centralized_source, search_path, 'browser', 'test2', 'aurora')

        # Check searchplugin data
        single_record = self.p12n.data['locales'][
            'test2']['browser']['aurora']['searchplugins']
        self.assertEqual(len(single_record), 0)

        # Check warning
        single_record = self.p12n.errors[
            'locales']['test2']['browser']['aurora']
        self.assertEqual(len(single_record['errors']), 0)
        self.assertEqual(len(single_record['warnings']), 0)

    def testCentralizedExtractInfoSearchpluginAA(self):
        # Read en-US searchplugins
        search_path = os.path.join(self.files_path, 'en-US', 'searchplugins')
        centralized_source = os.path.join(self.files_path, 'list.json')
        self.p12n.shared_searchplugins = {
            'browser': {
                'aurora': ['google', 'twitter']
            }
        }
        self.p12n.extract_searchplugins_product(
            centralized_source, search_path, 'browser', 'shared', 'aurora')
        self.p12n.extract_searchplugins_product(
            centralized_source, search_path, 'browser', 'en-US', 'aurora')

        # Read searchplugins for locale 'aa'
        search_path = os.path.join(self.files_path, 'aa', 'searchplugins')
        self.p12n.extract_searchplugins_product(
            centralized_source, search_path, 'browser', 'aa', 'aurora')

        # Check searchplugin data
        single_record = self.p12n.data['locales'][
            'aa']['browser']['aurora']['searchplugins']

        # Name should fall back to English
        self.assertEqual(single_record['google']['name'], 'Google')
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
        self.p12n.shared_searchplugins = {
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
        self.p12n.shared_searchplugins = {
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
