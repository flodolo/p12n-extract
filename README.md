Productization
=======

p12n_extract.py: extract data from searchplugins, productization errors, productization file hashes and save them in JSON files

Usage:
```
usage: p12n_extract.py [-h] [-p {browser,mobile,mail,suite,all}]
                       [-b {release,beta,trunk,all}] [-n] [--pretty]
                       config_folder

positional arguments:
  config_folder         Path to Transvision /config folder

optional arguments:
  -h, --help            show this help message and exit
  -p {browser,mobile,mail,suite,all}, --product {browser,mobile,mail,suite,all}
                        Choose a specific product
  -b {release,beta,trunk,all}, --branch {release,beta,trunk,all}
                        Choose a specific branch
  -n, --noproductization
                        Disable productization checks
  --pretty              Generate pretty output
```

This file has to be used on an existing [Transvision installation](https://github.com/mozfr/transvision).
All views are visible at http://l10n.mozilla-community.org/~flod/p12n/

Tests can be run with
```
python -m unittest discover
```
