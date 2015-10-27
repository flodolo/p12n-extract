<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset=utf-8>
    <title>Check High-Res Icons on Fennec</title>
    <style type="text/css">
        body {
            background-color: #fdfdfd;
            font-family: Arial, Verdana;
            font-size: 14px;
            margin: 0 auto;
            padding: 10px 20px;
        }

        h1 {
            margin-top: 20px;
        }

        a {
            color: #0096dd;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .filter:after {
            content: '';
            display: block;
            clear: both;
        }

        .filter li {
            display: inline;
            float: left;
            padding: 8px;
            border: 1px solid #000;
            background-color: #888;
            margin: 0 4px;
        }

        .filter a {
            color: #fff;
            text-decoration: none;
            text-transform: uppercase;
        }

        code {
            display: inline-block;
            background-color: #e1e1e1;
            font-family: monospace;
            font-size: 13px;
            padding: 4px;
        }

        .green {
            color: green;
        }

        .red {
            color: red;
        }

        table {
            border-collapse: collapse;
        }

        td,
        th {
            border: 1px solid #ccc;
            padding: 10px;
        }

        .sp_image {
            padding: 0 4px;
        }
    </style>
</head>

<body>

<?php
    $file_name = '../searchplugins.json';
    $json_file = file_get_contents($file_name);
    $json_data = json_decode($json_file, true);

    // Supported locales
    $locales = array_keys($json_data["locales"]);
    $locales = array_unique($locales);
    sort($locales);

    // Supported channels
    $channels = [
        'trunk'   => 'Nightly',
        'aurora'  => 'Developer Edition',
        'beta'    => 'Beta',
        'release' => 'Release',
    ];
    $channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : 'aurora';

    $repositories = [
        'trunk'   => 'https://hg.mozilla.org/l10n-central/',
        'aurora'  => 'https://hg.mozilla.org/releases/l10n/mozilla-aurora/',
        'beta'    => 'https://hg.mozilla.org/releases/l10n/mozilla-beta/',
        'release' => 'https://hg.mozilla.org/releases/l10n/mozilla-release/',
    ];

    // Only interested in Fennec
    $product = 'mobile';
    $product_name = 'Firefox for Android';


    // Known assets
    $known_assets = [
        'amazon' => 'data:image/png;base64,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',
        'bing' => 'data:image/png;base64,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',
        'duckduckgo' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGAAAABgCAYAAADimHc4AAAAAXNSR0IArs4c6QAAGKVJREFUeAHtXQm4HFWV/qv37e15W/KSvCwQSUIgJIEIArIIRD4UFDWCKBDCMIJsH6g4KKB+CjIKOmjAIaAQMjIwH8oMMCiKAoFIFoFsQHaSvLyt3+t+3a/79T7nVKf63a7XXV1V3R1CJuf7uuvWXc6995yqu5xz7ikJByCTyUi7L5m/JJPBkgwwm6J9StqRawUoIEkhKZPZJElYPnnl2uWSJBGZAYn/dl6+oC0TyzyOTOZsvj8CVaaAJL0kOaXLpvxmTbeFn/wjxK8ywdXo6UFnmjPtpV1fnndVOoN/V+c5cl99ClgkLLXQQHRV9as6UkMhCvB8S0MQZhVKPBJXfQrwYsdC1RxZ7VSf1sVq8NmKpRxS8VYb7C0TYGvtgMXjg8XlgUQ/i9OFdGwEmZEI0vyLhJHs2YtE7z4glTykulCsMYckAxyTj4Zr1gK4PjYXjo4psBHxJWKCXsgQ8ZPEhPjenRh59x8Y2bQG8d3v6y1+UPNJOxbPkzcEB7VWdWUWK9zHfRy+Uz8N9+wTYa2pV+co+z4VCiC68U2EX30e0bffANKpsnFWAsGHygD7+E7UnH0xfKecC2ttQyX6owtHamgQ4VUvIvTS00h07dJVplqZPhQGODpnoP6zV8Bz4pmQLLwOKA7JgT6ZSIn9u+VraihA4/0wMlEa8+MjsDhckNw0H7i8xMR6MFPt7ZPlq62xuThiSsmk04i8+RcE/vAo4rve08xbrcSDygBbSweavnozPPNOK9qfVMCPyIbVGNm4BtFNa5HydxfNWyrB2tQG96z5cM1eAM+xC2GtbypaJLLuFfgf+xnNHXuL5qlGwkFhgGR3oO4zl9NTfzk4rAZ+kiNr/4bwK88h+s5qfjTVWcq/lyxwz1kI32nnwzP/dPnNUSPNJOL0NvwGwWd/Aw4fDKg6A5zTZ6P5uh/CTktINfDEOPTC7zD04u/kJaQ6vVr3vJStPXcxahctLjjhJ2gp2/fA7Yht21itJuTwVpUBted/BY2Lr4Nky19C8no98MxyDP3paWRi0VxjDnZAcrpR+6mLUXfhlbB6a/KqzySTGPjdAxh6bkVefKVvqsIAye1FCz31nhNOHdNeXgb6n7gf6eDAmLQPK8JS14jGS29Azannj2kCzw29v/wuTfrDY9IqEVFxBnBn2r79b3DSSkeEJE2mfb+6EyOb14rRh1TYdcw8NH/9LtjGteW1K0YrpO67v1GVh6aiDOBVTtt3Hhgz3kfWv4a+ZXcgHQ7KHUt4ablIc4K7rl5e//MegEUK6aEBpOjNSHTtpqs/jwgH68birZWZoH57eV7o/tF1FV8lVYwBTPz2ux6GrX5cjlakcMDgk79EkFYWOSCdXGjC0QjNWIDOmcdi/Imn0RxhzyUrgfg+EiNsXofI+ldp5/o6rYwO7oa97oKvoeHLNH9RexVIBvqx/46rKsoE6w2zx9+pVGD2ysNO+/cegl14dVke07/sLoT+9NQYtM4hP2p2voPEnu1I0AbK23l0Xke5AL8Vzmkz4fvEIvp9GqANW3zvDiCZGIOvGhGx999GonsPzWP0gBzYLLIQ0HPCJxB+448VWzyU/QbwhNv+vV/njfk8nPTe983sk1uAOqlp9ORfvJTW5SdR56wFchSOSg72w//I3bRn+GvhDFWI5b1Dy83/KkteFfQ8J+z//tUVmZjLfgNab/oJ3MecoLQN/OT3/uzWosRPeOoQOedSpEkMMLBzKwL0i+7bjVhvF0BLUpuvliSfhZliIZGD7+RzYJ/Qiehbqw6KyJnF2/Edm+H9+Dm5N4GHWUfHNAy//mKu32YDZb0BvM5v+sqNubp5zO9fdidJHJ/LxakDCV8DIuMmwpKMQSImZGiPkLHakXR6kKTJOeWtQ01LG9omTUbjrBNgpU1TIYhuWY+ee66noWCkUHLF47ynnIfma3+QN1T6V9xf9j7B9BvAO1xe6yvjI/c48NSDtKt9UrPzVhI7uAI94HnAERqAM9gPJ927+/fCu38b6sN+1FlSsJGyBSRoczS1FsRnb26XRdcZmhOSg33IEN5qQmLPNqojDvexJ+Wqcc+cjyjJrVIDvbk4owFTbwDLcybc+595y83IW6/LT6TRBnB+C8n/a8/+PLynLIKDhhejwFJNHiZ4Zy2/fVVcMbXeel/eBpOXp/tu/aJp2ZGpN6D+oiXwnnhGjk4sMu7+8XWGn8KMzQEvDWPtNMl5SCHD4mQzwEtFW2MLvAs+SU/oQnl+YDVlNYCVOb6Tz5VVo4zfSnNWJpXCyJZ1pqrTFsYXQMnrfZZqitD/4F1Ik2DNCMQa2zHu5nvResk3SJbvNlJUM6/r6Dnyqow3VNWA9PAQ7ejvyEPN9GC6mAHDDGB5vihSHl79kjwOGql8pGk8Wpd8C7VzTzFSTHdee/sktN7yU3o884WAuhGUyMhPe0hYaDA9mC5mwBADWJMlKlPYEsH/GHXUAKRpYrWedykaaENTTWCFPg+V1YKBJ36O1HAoh57pwvQxCoYYwGpEEYLPPoYUrUCMQM/8RZiy8HQjRUzn5fY6Jh1lurxWQZbmBn//SF4WNX3yEovc6GYA61pZh6sAy/RZkWIEEjWN8B01K09kYaS8nry7tu7Fk4+/gueefh3DwzE0XfFNPcVM5eFVFyuVFGD6MJ2MgG4GsPWCuObnypkJRmDgYwvRWl83pkigqxsvrPwznnz4RWxY9faYdL0Rr//5LSxbsQG7Pgjg3fd68d0fvIjtYbds8qIXh5F8rExijZ4CTB+mkxHQN0uRvIZNRxRgbVHwhZXKre4rS0FrWtrz8r+w4o94em0U8ydaMWd6LQZ7A9j29lZMP8740DGxzYt77lqUwy9LYz/YA+8XrsnaAuVSKhfgUaDuwstzOmam08CK+3TbHeliABtNiXY7LB42qtHi4Yd/dsEyYeeq1Xh1lwX33nISGjvylSBmSDRxVj7TeH/QOHmSjMoxbRbi2zeZQatZhkeByJq/0gN6npyP6cT0iv7jNc1ySqKuIYgt1kRgtaJRiNVlbXSsHm+uaG8wgRsvm10R4ueQFgmw7rdaoKaHml5a9epigCj/kDm+/hUtnAXTEiRkYxBtPOeePg8tk8t/8gtWqIr0LvxU3v5FlVzWLZvSsD2TAiK9lLhi15IMYENZq2904oySlsqMzUzKkd3t8t5BAYfXowQrfn35qf/Br+95GJvXbZRxsyU1G/xWBciOiY3JFGB6Md30QEkGqBttVqlOJwTl9qRKWBf09QXAv3Lgvtt+hhue2Irla4aw7rX1ePn5V2V0aj1vOXWoy7IlnwhquolpYrg0A46ZK+YnU2+TVg0HrJFjg6Ovqog4EU/in6/5Kc5a+qj8u+kb94HjjEI6HsN/vZu1aqtzWTFzajOeeCb7dDqPOtYoOt352YxSBJeKbmKaGC7JAMeEKbn86ZEo4h9szd0bCdiHsxYRI8HBgsUeuf9xrOrONuc7ZzfjjuvPw6M/f7xgXq1INgJzS1nGdYfTuGL5u3A7sho2uS8GVKBa9ajT2IaVpcIKiHRT4gpdtRlAwiw+HKFAovsDJWj4ag9nCR8eHi5Ydsv2nlz8slX9uOX+l7B522hcLrFEgHXMNy6aAjuy9v/1ljiuu+Z8uRQLzcT+lEBlOFk0dZfr0SEM1NwH8LEgcdWS2G+eAc5QdugJxBIFOzajoxZ/6YnJaYPRDN7cl8S1C8zpB85feinmn7UL29/biTmnzIevtiZXJ+uVdQNbpPAvra8Em9C7yRKbgenG9OM4LdBkAJ/JEiFZxhtgIZWhfTgA/4HlqIiXw0tu+ireuvaneCOYleOfXB/CFTf+kzqb7vvWqZ3gnxokksZqgdWXhPeYIXiPDcLRSmpOYkA6YkOsy43w22RQ8N4oM9V4xDeA05h+ZTGArYhFEAVPYrzesMvfhRAp3uP93XAINkRc3uHz4cFHv4uud96ROz3+2Dl5sie9dZTKxwqVPCACO8ePwD0lBNe0YTgnjDUWtniScE8Pyb+elZMwsmt0Myni4sMjIqjpJ6YpYc03gA2RROBJuBxwDexHaNJMRLr3jmEA42Vh1oTjjy+nCs2yGVqJ8TzmaCHDgM4IXJOH4ZoUheTUd14sGXAg3ussWgef3BFBTT8xTQlrMoCNrkQoV8/qHiDbH4KhwCDMje5ia3SE00SQFM098Z1AdD1S3c9j4vWbSQWqj+C5GlJkTvlWPQJ/a6bjUdkVVS5NCPCxKRHU9BPTlLAmAyyOfG5naI1dDjj798nFByIxZEVk5WBTlSUCZwYfByK05k/sJcIHyZw0n9Dykk97CshDmolbEHq7HqE3G5EM2vPSCt3wSR8R1PQT05SwJgN4UyOCxLY6ZYAtFoEtHEC/SeuHglXHyV6n+3vIBP9QMNlMZLzbhfBbDQhvJIsHYoJe4AODIrCJZinQZID6UIKeMa1UhR4ahoZ8hSfiUmXHpPsfQGb/7fSkF17ajsmvEZEetiG8qRbDG+vADDADfFpTBD1DtiYDRMEZI9YzpokNKBTmldAQTcTh/XvQqFoJFcpfLC7TfRsyfb8olqwrPhOzILK1BsNE+OhOWvHpXO8XQ85HZUVQ009MU8LaDFCpHEWljILA6NVFJogMPBE3Gi2s5B96VpP4PF4n+lywuJOw1SToIB6JJnhDRcArmZEPPPJ6PrqDCEYTbKVAbVimR2WryQC2DBbBPn6yeGsqrKyEBqIJdJrCQGc1wi8XLRnd4UPvUxOJsEIWorGFlpppHs/TlSO4UIMcVCvk1fRT5+d7zRmGvY6wubkC9vZOJWj6yjtiBxnm9qcs8kl1U4gs+RtEEUdwFR3GFonPiSQJl5ePVSQ+V8Mn9BVgusleW5SIIldNBrDLF/Y6ooB81rcC0kSXfx+SpB+I94ziVurQc5XqPls0m3CiqGieaiWIb4BMN+HhLVanNgOoFJ/VUoBFvXxsqFxwEwMYwiYZAPd8SHWfKdiM+jN6aWdrfjaV7BnaHUdQc0IAdQv9ZPSr78Q8n6wUfVOwqxw9oDkHMIKRLf+Ad/4nc7hY0xPbuiF3bybgOrAhCwSCoAHDFEgdD9GMSucCht/IK8+ynAlLdyC4ugmRd2uQCmt0kaYDOxGYRROOjihcHZGsAE54LB3tUfQ9ky+UzKvwwI1aA8Z+ivSARuuyxdnZkQhuYoDaJE9M1xN2D3bTuJxGfyyDaXoKFMpjIalp5/MI/Gomaub2kdnM6FxlrU2g8Zxu+ZcaohXRgIOOwfKcQxSn+cDqSckrI1tdnE5oUkQR4GVqcPW4Iqn50UwXEdR0E9PEcEkGsKepFJ3vVRTzrhnH0X7ARwfUwiIeQ2EpGadTMn0YaGyTFfyitbURRKlBss9cVY/g6/VwTw3DO3sIHpJaikMQM4N/RiHyvg+Df2rTJYIgKaLsCESpg6XGej10lWQAI41u+Dt8dEiNgYnlXXg2wi//Xr43++fy78VIQyuiXbvhmXyUKTQ58S89xNHtPvkHcsTp7iQp51T6TaQhpS0r09dTQcLvoP0B7YZpY5boy5eDaZWXDdfoqK4CTC+9oIsBbHikMIAR15DLl3IZ4KZ5IDB9HkK93aYZUPCIKw0zvBfgH4PkSGcZ0UHjfGMMtoYE7QlouKJxPj1sRWLQKYseeFNmhOgy8gN/7AJHhPBrL4i3mmFdDOBjOezmS9kJO2ccL2t79Gw0itXOS1GGwFAIrcUylYiXVNLaQtlZmJZ7OwplKDPOQl5WPPNOz2FhOsk+6XIx2gFhvtfISIoM9rGmANtc1l3wVeXW1NU52AMplUBfovgkWAqxtcgJylLlKplee95iMswdHa5kOhlwCKiPAdRidnDHpxEVqDn9AlgbmpVbw1eJVkGugW4M0ZCRUsmc9CLjjpfTBr31FMvHvktrz/tyLpnpw3QyAroZwApndnCnADvYYIcW5YCyIYvo3LQUqsvMsaBCeMzE1Z7zRfmUpFKW6aNWzCtpxa66GcAI2LugCGxxbBcMt8Q0PWFX314521B/r57sBfPwqcgPA6zkrkB9WlRNHz3tMsQAdu3IHqQUYNFE05XfUm4NX90D2Yl4MJyvSzWCyCX4qdBTrqvFjhGHoW4XRNt42U25s8KcgelixvWl4Zawa0fROpqP68vuZAo2UzuSpaIWOubTmzTcjBxi51FzyB1l6d3q5qlOPPSFevz2M7V4bV7WUjuHxGDARe4K+LC2Aqy6ZbqYAcM9Z7+a7NpRBD4IZ/agMusHRkhbkvDnmyHuCr2Pn6y7Gcs33YO/d/8FsVRhkxg2ZREPD4rt4rC/3ooVF9ThD2f6MFCXtWjYNsmhzqb7nt0qNF9zZ17+4LO/Ne3EyTADuGb2q8k+EhRgA6SWG35s6mC0q2+PjCbc9YGCTr5uD2zCNvox8ZkJt7y6GI9tuQ97wzvy8vEN78xT9DmKIS/JbmqscjhDYp/Vc9x45KJ67GnN3+4M1liQtFIGE8AuOEWpJ9OB6WEW8lumEwsPQexXs/2Oh3MuKZ1Tj0HT126RHSrpRCNnU1ZCwQE/GoSCKZVJSSw1gte6/lf+zWw8AYs6F6PDNw0rNi/DlsG1iFw5Kgpo7U/CQZvdPW3FuxckJjQF1JoboQEFgvWfvxoecuCkAB9WZDqIQ7KSpvdavIUlMLBTU/arKfoL4lVRkly3GJGWekhJz+CPxNEph7J/fMKxGGweWI+1+9chRrvchpqx+XrGle5WxEUMGKM6K1YjfeXijAvRcPHVeRm4/+U6dzU1BCmtYKem4qqI4xu/9HW5sUqeUldrNAR7JAh/hhw3CRokm0WbiENk9CaVYcYQc+gfgjykDxl31W15XeF+V8Kpa1kM4BaxU1P2oSYCN5afGL3gpv0AS/Njgvm73aI9UfIbQlJg08BzhB7wLDgDLdf/iOrKTuBchvvL/a4ElNGFbPVsvMVOTcVJmRvbfPXtqFP5lijWYEUwF+7ODkecz2XTsVQcO/oUq2JMvHJmbUyCEFFDTqRabrwn73Ql95P7qzZaE4oZCpbNAK6ND23LTk3Jr6YIjYuvpY3at0uujlg0zTAYCOSKu21ZcXIuQhXgKYK+f2YanFqqXnq1Gr50LcYtuU222FYqYb+h3E+jh9SV8oWuFWEAI+b9ATs1Fd8EjueJefz3H9U8GiS/AUTR/vjoqqTWIa6JGFM+sPVDmE7SiOf4UiQrjJCa87Q3Izjr7xG0+EfVlPmlaVIdHhUsimnWhnFou30Z6i+8QoyW+1Vpp61cQcUYwMiYCV13XDlmTuAl6oQfPwEvOWEtBBZSUTqDfRikiTh94Bhro6ulUFY5jsXhx7XNRDIpYW9vGju6sr+RcDM+N/UanBmbgRM3RLHkmSCWPh3AqesiaPOPMtdFTGoIjd4rFXkWfBIT7v4PuGfOU6LkK4/53K9qfNzBlNO+vNYVuGEb0hZy8Sg6d1KyRcl5t3/53WOkhvtPvgiBaXNx2tGT4Zs+S85+0ysXYzgRUorK19lNC3DR9CswkfYA/qgfPeEe2Og8VrOnGQ2u7FvDFgn771qaV45veOnZ00SnKGMptPWPMoAP1DVdfis8cz8xtgytdqrpPd2U074xrVRHkCtJdmrKxqksKxLd3Nibx6PmrM/JnhL5yKtiQZx01yDcMQONtOGqmThVxrhz6F10D++Bhcbk45o/jq/NvJk2YF9CnSO76fLYPWj2NqPJ3QS3MGnbxrXT+nyD/E0xsWn2ZPbJ90Wyk4eFHGvUf26p7Kzb0ZGtU8kvfz9g5S8w8Nt76dU2rtRX8JS6VuUNECvV+oIG+/wM/fVZBP/7cYSTaew8/xp0WhOYeW7W8q1/pBtbBzfgGNr51juNWRDFdmxB179cJjYlF2YjKnY6W3PmhTk3M7lECvA8dlh8QUPpFFtSaH1Dhtf0PGxsi5E8p6cLp5ybr+RW8Bi99pAL5cial+Vi/LUM70lnyd8qc6neSgUvixQOu2/IKJ3jKx/bbLpM+ytKaSICf0cAtJewkqmHuAEScekJ85McIvMZHgadHzu+4NOu4OGd7WH7FSWlk8rVyHfE2CkqHy3lp5PHZclqoY2Rk4yv6PthdJXjE7HslZXhNLxLDodsSCYeMlfqFq//774jJnaew2xNfORLeofatyRJ26SYQaoZVs49m1eyxRobmR35lmQJSua+pkouX9jrCK/TSw0nIkqWqspfU+XPoJB195GvqYrUMRM+4Pgi73vCtNljuyDWx7JgjPccfCaLrfWOfE/YDJG1ytATzU4vSjm+0EJxqKZVVBZ0qHbyUG4XM8C8of+h3LOPQtskKWQhweKmj0JbD8c2klJoExlz4OHDsXMfhT7Rw7+cmJCRdl6ygL5Mljn7o9Dow6aNkvTSlJVrzqEhSMpITuky+j7TS4dN5w71jhCtmeYy7ZW28puw+5L5S0iUchUJJ1kjoq2UVQoeueqlQJiG+4087ExeuXY5E58L/h92t1mNYWFeRQAAAABJRU5ErkJggg==',
        'google' => 'data:image/png;base64,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',
        'twitter' => 'data:image/png;base64,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',
        'wikipedia' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGAAAABgCAQAAABIkb+zAAAEXElEQVR4Ae3cA4xs2QJG4b9aj41n27Zt27Zt27Zt27Zt+2KM5lXP924qOzs56VPjKSRnrfhv7NUsp08v98+PsxwT4nJ+nPunl8KZ8/WYQL+eMydJrx5/EhN603lAHpVJ5bzZNp035uyZXM7Uy3LmM7ms9CITzVRGQRfQBXQBXUAX0AV0AV1AF9AFdAFdQBfQBXQBXcBMjgU7M5+1vqtZyXqmc/IsZDEL+53LP3KRPCCv62+rWd7vnpx6v/M5Vd935OF5R+6RtRyeQ3NYNjKTxZw2p8tiduR8uWfemY3+dkjWM5fFLPU/8q9yrRwbHLP39WkHamPD5z1WnNMb/dpW/uAdbipu413+qcl2H3N3cXFv9DNNtvmg68uxMY6dPfewTZNvOauouqEdULlTY532IgqHua+ZxnoHm4ANj63biRNQPZedVPY5nTR1BXsVbNuyTlsDqy4tW/wK4NYiJ01AXMlm+wGrL1cDnWbLejC4m7T4DvAOOSkD4v0qztGyLzpUweOk4UXAp6XV72DNmU7qgPPao+DJ0uLTFPzXXGN5I9acU1o8vb14jZzUAfFeBdvMtuwLDlfwIKme2248T1p9MvY59zACLqcy4Kf5eQr+ZbYRfrAFaXHaf/FRGUZAfE/Bzwf8OKwpuJ/0vYRNPEpavT248rACbqfimtLiqxT8w4yIz+Of5qTV7+LHMqyAaf9R8Clp8ex2K7iXuAa4q7R6aXCH4QXE4xVsOq+0+DYFfzXth/ilnrT6Tvzb9DADlqwqDPjTd377FHwI3EBaPb0NPFaGGRBvoLBiUVr8EFS+KgN8KpYtDjvgQo6i8Hhp8ZJUjnIZaXXGNrxKhh0QX6TwHzPS4uco/FtPWr0T9jnPKAJuOOiCc/UqVG4jrf4AH5dRBPT8UWHg3/BvUfhl635ZcNXRBMSDqFxFWvwklVu07O/BT2RUAad0GIWPtexXgMpPt+xntAt3Gl1AvJjCPufasn4d/6NyY2n4DPzXzCgDzmEvhVdIw+tj02X8nsIPGvus7Xi8jDIgPkLhSPON5ad4t7gbletJ9S71n+BIA65C5dGal1g3nENM+weF70j1R3iNjDogfkrhn6br5dU/4UXS94EtF7+vUC8IjjzgrlRuJ33v27j2dTLbKXxd+r4Pn5RRB9RfxsL3y4H/i0dI9TFUrirOZDeuPh4B8TQqVyjH/ZtZqZ7KIRS+JJ6Fn8k4BNRL9IUPmncwbr/1b37lanbiLuMTEG+nsNfb8cOWq0DLFA7E/8yMU8AlNLnaoP/alSfKOAXEN6h8Qlo8k3UKq5bGLeAWFPa6oLT6Ogqvk3ELmPJ3wOtlgOe0B2w6//gFxCPBsjPIQN8JPi3jGDDvSDxVDPaCNnFNOdGczrNzIrEnRyR5XPZlMIdmKofnpTnx6B783QV0ARNOF9AFdAFdQBfQBXQBXUAX0AV0AV3A6OgCVjLJrEzlj5lk/jiVt2eSefvEvzTJxL84zMS/PM//Af1pBRpQAkXBAAAAAElFTkSuQmCC',
        'yahoo' => 'data:image/png;base64,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',
    ];

    $locale_with_errors = $locale_clean = $error_numbers = 0;

    $html_intro = "<p>Last update: {$json_data['metadata']['creation_date']}</p>\n";
    $html_intro .= "<p>Bug reference: <a href='https://bugzilla.mozilla.org/show_bug.cgi?id=1179109'>bug 1179109</a>.";

    // Create channel filter
    $html_intro .= "<p>Filter by channel</p>\n";
    $html_intro .= "<ul class='filter'>\n";
    foreach ($channels as $channel_id => $channel_name) {
        $html_intro .= "<li><a href='?channel={$channel_id}'>{$channel_name}</a></li>\n";
    }
    $html_intro .= "</ul>\n";
    $locale_list = [];

    $errors_detail = [];

    $table = '
<table>
  <thead>
    <tr>
      <th>Locale</th>
      <th>Images</th>
      <th>Errors</th>
    </tr>
  </thead>
  <tbody>';

    foreach ($locales as $locale) {
        if (isset($json_data['locales'][$locale][$product])) {
            if (isset($json_data['locales'][$locale][$product][$channel]['searchplugins'])) {
                // I have searchplugins for this locale
                $table .= "<tr id='{$locale}'>
                             <th>
                               <a href='#{$locale}'>{$locale}</a>
                             </th>
                           <td>\n";
                $errors = '';
                $locale_errors_detail = [];
                foreach ($json_data['locales'][$locale][$product][$channel]['searchplugins'] as $singlesp) {
                    $spfilename = strtolower($singlesp['file']);
                    // Android only has one image
                    $image_index = $singlesp['images'][0];
                    $image = $json_data['images'][$image_index];
                    $table .= "<img class='sp_image' src='{$image}' />\n";

                    $plugin_has_error = false;
                    // Check if the asset is obsolete
                    foreach ($known_assets as $provider => $known_image) {
                        if (strpos($spfilename, $provider) !== false &&
                            $image != $known_image) {
                            $errors .= "{$spfilename} uses an outdated image.<br/>";
                            $plugin_has_error = true;
                            $error_numbers++;
                            $locale_errors_detail[] = $spfilename;
                        }
                    }
                    // Check if the image is not 96px, but only if there are no errors
                    if (! $plugin_has_error) {
                        $image_data = getimagesize($image);
                        if ($image_data) {
                            list($width, $height, $type, $attr) = $image_data;
                            if ($width < 96 || $height < 96) {
                                $errors .= "{$spfilename} uses an image smaller than 96px ({$width}x{$height}px).<br/>";
                                $error_numbers++;
                                $locale_errors_detail[] = $spfilename;
                            }
                        }
                    }
                }
                if ($errors) {
                    $locale_with_errors++;
                    $locale_list[] = $locale;
                    // Save list of searchplugins with errors
                    $locale_errors_detail = array_unique($locale_errors_detail);
                    $errors_detail[$locale] = $locale_errors_detail;
                } else {
                    $locale_clean++;
                }
                $table .= "      <td>";
                if ($errors) {
                    $table .= "<a href='{$repositories[$channel]}{$locale}'>Link to repository</a><br/>{$errors}";
                } else {
                    $table .= "&nbsp;";
                }
                $table .= "      </td>\n    </tr>\n";
                $table .= "      </td>\n    </tr>\n";
            }
        }
    }

    $table .= '
  </tbody>
</table>
';
    echo $html_intro;
    echo "<p>Locales with errors ({$locale_with_errors}): " . implode(', ', $locale_list) . ".</p>\n";

    // Consider eBay a special case
    $locales_ebay = [];
    foreach ($locale_list as $locale) {
        if (isset($errors_detail[$locale]) &&
            count($errors_detail[$locale]) == 1 &&
            mb_strpos($errors_detail[$locale][0], 'ebay') !== false
            ) {
            // The only error for this locale is eBay
            $locales_ebay[] = $locale;
        }
    }
    $locales_without_ebay = array_diff($locale_list, $locales_ebay);

    echo "<p>Locales with errors ignoring eBay (" . count($locales_without_ebay) . "): " . implode(', ', $locales_without_ebay) . ".</p>\n";
    echo "<p>Clean locales: {$locale_clean}</p>\n<p>Errors: {$error_numbers}</p>";
    echo $table;
