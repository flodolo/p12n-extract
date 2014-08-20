import collections
import os
from p12n_extract.p12n_extract import extract_splist_enUS
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

        self.assertEqual(
            sp_list,
            ['google', 'twitter']
        )

if __name__ == "__main__":
    unittest.main()
