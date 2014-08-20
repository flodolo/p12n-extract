import collections
import os
from p12n_extract.p12n_extract import extract_splist_enUS
from p12n_extract.p12n_extract import extract_sp_product
import unittest

class TestSearchpluginAnalysis(unittest.TestCase):

    def setUp(self):
        nested_dict = lambda: collections.defaultdict(nested_dict)
        self.json_data = nested_dict()
        self.json_errors = nested_dict()
        self.filepath = os.path.join(
            os.path.dirname(__file__),
            "files"
        )


    def tearDown(self):
        del self.json_data
        del self.json_errors
        del self.filepath


    def testListEnglishSearchplugins(self):
        search_path = os.path.join(
            self.filepath, "en-US", "searchplugins"
        )
        sp_list = []

        extract_splist_enUS(search_path, sp_list)

        self.assertEqual(sp_list, ["google", "twitter"])


    def testExtractInfoSearchpluginEnglish(self):
        search_path = os.path.join(
            self.filepath, "en-US", "searchplugins"
        )
        splist_enUS = ["google", "twitter"]
        images_list = ["data:image/png;base64,iVB"]

        extract_sp_product(
            search_path, "browser", "en-US", "aurora", self.json_data,
            splist_enUS, images_list, self.json_errors
        )

        # Check searchplugins data
        single_record = self.json_data["en-US"]["browser"]["aurora"]
        self.assertEqual(
            single_record["google"]["name"], "Google"
        )
        self.assertEqual(
            single_record["google"]["description"],
            "Google Search"
        )
        self.assertEqual(single_record["google"]["secure"], 1)
        self.assertEqual(
            single_record["google"]["file"],
            'google.xml'
        )
        self.assertEqual(
            single_record["google"]["url"],
            'https://www.google.com/'
        )
        self.assertEqual(len(single_record["twitter"]["images"]), 3)

        # Check number of extracted images
        self.assertEqual(len(images_list), 7)

        # Check errors (should be empty)
        self.assertEqual(len(self.json_errors), 0)


    def testExtractInfoSearchpluginAA(self):
        # Read en-US searchplugins
        search_path = os.path.join(
            self.filepath, "en-US", "searchplugins"
        )
        splist_enUS = ["google", "twitter"]
        images_list = ["data:image/png;base64,iVB"]

        extract_sp_product(
            search_path, "browser", "en-US", "aurora", self.json_data,
            splist_enUS, images_list, self.json_errors
        )

        # Read searchplugins for locale 'aa'
        search_path = os.path.join(
            self.filepath, "aa", "searchplugins"
        )

        extract_sp_product(
            search_path, "browser", "aa", "aurora", self.json_data,
            splist_enUS, images_list, self.json_errors
        )

        # Check searchplugin data
        single_record = self.json_data["aa"]["browser"]["aurora"]

        # Name should fall back to English
        self.assertEqual(
            single_record["google"]["name"], "Google"
        )
        # Description should include "(en-US)"
        self.assertEqual(
            single_record["twitter"]["description"],
            "(en-US) Realtime Twitter Search"
        )
        # Missing image should fall back to record 0
        self.assertEqual(
            single_record["wikipedia-it"]["images"],
            ["data:image/png;base64,iVB"]
        )

        # Check errors
        single_record = self.json_errors["aa"]["browser"]["aurora"]
        self.assertEqual(len(single_record["errors"]), 9)
        self.assertEqual(len(single_record["warnings"]), 1)

        self.assertIn(
            "there are duplicated items (google) in the list",
            single_record["errors"]
        )
        self.assertIn(
            "file extrafile.xml not in list.txt",
            single_record["errors"]
        )
        self.assertIn(
            "file google.xml should not exist in the locale folder,"
            " same name of en-US searchplugin",
            single_record["errors"]
        )
        self.assertIn(
            "error parsing XML (aa, browser, aurora, broken.xml) "
            "<code>mismatched tag: line 18, column 2</code>",
            single_record["errors"]
        )
        self.assertIn(
            "no images available (aa, browser, aurora, wikipedia-it.xml)",
            single_record["errors"]
        )
        self.assertIn(
            "file referenced in list.txt but not available "
            "(aa, browser, aurora, wikipedia-aa.xml)",
            single_record["errors"]
        )
        self.assertIn(
            "searchplugin contains preprocessor instructions (e.g. #define, "
            "#if) that have been stripped in order to parse the XML (aa, "
            "browser, aurora, wikipedia-it.xml)",
            single_record["warnings"]
        )


if __name__ == "__main__":
    unittest.main()
