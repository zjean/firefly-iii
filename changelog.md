# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [4.7.2.2] - 2018-04-04
### Fixed
- Bug in split transaction edit routine
- Piggy bank percentage was very specific.
- Logging in Slack is easier to config.
- [Issue 1312](https://github.com/firefly-iii/firefly-iii/issues/1312) Import broken for ING accounts
- [Issue 1313](https://github.com/firefly-iii/firefly-iii/issues/1313) Error when creating new asset account
- [Issue 1317](https://github.com/firefly-iii/firefly-iii/issues/1317) Forgot an include :(

## [4.7.2.1] - 2018-04-02
### Fixed
- Null pointer exception in transaction overview.
- Installations running in subdirs were incapable of creating OAuth tokens.
- OAuth keys were not created in all cases.

## [4.7.2] - 2018-04-01
### Added
- [Issue 1123](https://github.com/firefly-iii/firefly-iii/issues/1123) First browser based update routine.
- Add support for Italian.
- [Issue 1232](https://github.com/firefly-iii/firefly-iii/issues/1232) Allow user to specify Docker database port.
- [Issue 1197](https://github.com/firefly-iii/firefly-iii/issues/1197) Beter account list overview 
- [Issue 1202](https://github.com/firefly-iii/firefly-iii/issues/1202) Some budgetary warnings 
- [Issue 1284](https://github.com/firefly-iii/firefly-iii/issues/1284) Experimental support for bunq import
- [Issue 1248](https://github.com/firefly-iii/firefly-iii/issues/1248) Ability to import BIC, ability to import SEPA fields. 
- [Issue 1102](https://github.com/firefly-iii/firefly-iii/issues/1102) Summary line for bills 
- More info to debug page.
- [Issue 1186](https://github.com/firefly-iii/firefly-iii/issues/1186) You can see the latest account balance in CRUD forms 
- Add Kubernetes YAML files, kindly created by a FF3 user.

### Changed
- [Issue 1244](https://github.com/firefly-iii/firefly-iii/issues/1244) Better line for "today" marker and add it to other chart as well ([issue 1214](https://github.com/firefly-iii/firefly-iii/issues/1214))
- [Issue 1219](https://github.com/firefly-iii/firefly-iii/issues/1219) Languages in dropdown 
- [Issue 1189](https://github.com/firefly-iii/firefly-iii/issues/1189) Inactive accounts get removed from net worth 
- [Issue 1220](https://github.com/firefly-iii/firefly-iii/issues/1220) Attachment description and notes migrated to just "notes". 
- [Issue 1236](https://github.com/firefly-iii/firefly-iii/issues/1236) Multi currency balance box 
- [Issue 1240](https://github.com/firefly-iii/firefly-iii/issues/1240) Better overview for accounts. 
- [Issue 1292](https://github.com/firefly-iii/firefly-iii/issues/1292) Removed some charts from the "all"-overview of budgets and categories 
- [Issue 1245](https://github.com/firefly-iii/firefly-iii/issues/1245) Improved recognition of IBANs 
- Improved import routine.
- Update notifier will wait three days before notifying users.
- [Issue 1300](https://github.com/firefly-iii/firefly-iii/issues/1300) Virtual balance of credit cards does not count for net worth 
- [Issue 1247](https://github.com/firefly-iii/firefly-iii/issues/1247) Can now see overspent amount 
- [Issue 1221](https://github.com/firefly-iii/firefly-iii/issues/1221) Upgrade to Laravel 5.6 
- [Issue 1187](https://github.com/firefly-iii/firefly-iii/issues/1187) Updated the password verifier to use Troy Hunt's new API 
- Revenue chart is now on frontpage permanently
- [Issue 1153](https://github.com/firefly-iii/firefly-iii/issues/1153) 2FA settings are in your profile now 
- [Issue 1227](https://github.com/firefly-iii/firefly-iii/issues/1227) Can set the timezone in config or in Docker 

### Fixed
- [Issue 1294](https://github.com/firefly-iii/firefly-iii/issues/1294) Ability to link a transaction to itself 
- Correct reference to journal description in split form.
- [Issue 1234](https://github.com/firefly-iii/firefly-iii/issues/1234) Fix budget page issues in SQLite 
- [Issue 1262](https://github.com/firefly-iii/firefly-iii/issues/1262) Can now use double and epty headers in CSV files 
- [Issue 1258](https://github.com/firefly-iii/firefly-iii/issues/1258) Fixed a possible date mismatch in piggy banks
- [Issue 1283](https://github.com/firefly-iii/firefly-iii/issues/1283) Bulk delete was broken 
- [Issue 1293](https://github.com/firefly-iii/firefly-iii/issues/1293) Layout problem with notes 
- [Issue 1257](https://github.com/firefly-iii/firefly-iii/issues/1257) Improve transaction lists query count 
- [Issue 1291](https://github.com/firefly-iii/firefly-iii/issues/1291) Fixer IO problems 
- [Issue 1239](https://github.com/firefly-iii/firefly-iii/issues/1239) Could not edit expense or revenue accounts ([issue 1298](https://github.com/firefly-iii/firefly-iii/issues/1298)) 
- [Issue 1297](https://github.com/firefly-iii/firefly-iii/issues/1297) Could not convert to withdrawal 
- [Issue 1226](https://github.com/firefly-iii/firefly-iii/issues/1226) Category overview in default report shows no income. 
- Various other bugs and problems ([issue 1198](https://github.com/firefly-iii/firefly-iii/issues/1198), [issue 1213](https://github.com/firefly-iii/firefly-iii/issues/1213), [issue 1237](https://github.com/firefly-iii/firefly-iii/issues/1237), [issue 1238](https://github.com/firefly-iii/firefly-iii/issues/1238), [issue 1199](https://github.com/firefly-iii/firefly-iii/issues/1199), [issue 1200](https://github.com/firefly-iii/firefly-iii/issues/1200))

### Security
- Fixed an issue with token validation on the command line.

## [4.7.1] - 2018-03-04
### Added
- A brand new API. Read about it in the [documentation](http://firefly-iii.readthedocs.io/en/latest/).
- Add support for Spanish. [issue 1194](https://github.com/firefly-iii/firefly-iii/issues/1194)
- Some custom preferences are selected by default for a better user experience.
- Some new currencies [issue 1211](https://github.com/firefly-iii/firefly-iii/issues/1211)

### Fixed
- Fixed [issue 1155](https://github.com/firefly-iii/firefly-iii/issues/1155) (reported by [ndandanov](https://github.com/ndandanov))
- [Issue 1156](https://github.com/firefly-iii/firefly-iii/issues/1156) [issue 1182](https://github.com/firefly-iii/firefly-iii/issues/1182) and other issues related to SQLite databases.
- Multi-page budget overview was broken (reported by [jinformatique](https://github.com/jinformatique))
- Importing CSV files with semi-colons in them did not work [issue 1172](https://github.com/firefly-iii/firefly-iii/issues/1172) [issue 1183](https://github.com/firefly-iii/firefly-iii/issues/1183) [issue 1210](https://github.com/firefly-iii/firefly-iii/issues/1210)
- Could not use account number that was in use by a deleted account [issue 1174](https://github.com/firefly-iii/firefly-iii/issues/1174) 
- Fixed spelling error that lead to 404's [issue 1175](https://github.com/firefly-iii/firefly-iii/issues/1175) [issue 1190](https://github.com/firefly-iii/firefly-iii/issues/1190)
- Fixed tag autocomplete [issue 1178](https://github.com/firefly-iii/firefly-iii/issues/1178)
- Better links for "new transaction" buttons [issue 1185](https://github.com/firefly-iii/firefly-iii/issues/1185)
- Cache errors in budget charts [issue 1192](https://github.com/firefly-iii/firefly-iii/issues/1192)
- Deleting transactions that are linked to other other transactions would lead to errors [issue 1209](https://github.com/firefly-iii/firefly-iii/issues/1209)

## [4.7.0] - 2018-01-31
### Added
- Support for Russian and Portuguese (Brazil)
- Support for the Spectre API (Salt Edge)
- Many strings now translatable thanks to [Nik-vr](https://github.com/Nik-vr) ([issue 1118](https://github.com/firefly-iii/firefly-iii/issues/1118), [issue 1116](https://github.com/firefly-iii/firefly-iii/issues/1116), [issue 1109](https://github.com/firefly-iii/firefly-iii/issues/1109), )
- Many buttons to quickly create stuff
- Sum of tables in reports, requested by [MacPaille](https://github.com/MacPaille) ([issue 1106](https://github.com/firefly-iii/firefly-iii/issues/1106))
- Future versions of Firefly III will notify you there is a new version, as suggested by [8bitgentleman](https://github.com/8bitgentleman) in [issue 1050](https://github.com/firefly-iii/firefly-iii/issues/1050)
- Improved net worth box [issue 1101](https://github.com/firefly-iii/firefly-iii/issues/1101) ([Nik-vr](https://github.com/Nik-vr))
- Nice dropdown in transaction list [issue 1082](https://github.com/firefly-iii/firefly-iii/issues/1082)
- Better support for local fonts thanks to [devlearner](https://github.com/devlearner) ([issue 1145](https://github.com/firefly-iii/firefly-iii/issues/1145))
- Improve attachment support and view capabilities (suggested by [trinhit](https://github.com/trinhit) in [issue 1146](https://github.com/firefly-iii/firefly-iii/issues/1146))

### Changed
- Whole new [read me file](https://github.com/firefly-iii/firefly-iii/blob/master/readme.md), [new end user documentation](https://firefly-iii.readthedocs.io/en/latest/) and an [updated website](https://www.firefly-iii.org/)!
- Many charts and info-blocks now scale property ([issue 989](https://github.com/firefly-iii/firefly-iii/issues/989) and [issue 1040](https://github.com/firefly-iii/firefly-iii/issues/1040))

### Fixed
- Charts work in IE thanks to [devlearner](https://github.com/devlearner) ([issue 1107](https://github.com/firefly-iii/firefly-iii/issues/1107))
- Various fixes in import routine
- Bug that left charts empty ([issue 1088](https://github.com/firefly-iii/firefly-iii/issues/1088)), reported by various users amongst which [jinformatique](https://github.com/jinformatique)
- [Issue 1124](https://github.com/firefly-iii/firefly-iii/issues/1124), as reported by [gavu](https://github.com/gavu)
- [Issue 1125](https://github.com/firefly-iii/firefly-iii/issues/1125), as reported by [gavu](https://github.com/gavu)
- [Issue 1126](https://github.com/firefly-iii/firefly-iii/issues/1126), as reported by [gavu](https://github.com/gavu)
- [Issue 1131](https://github.com/firefly-iii/firefly-iii/issues/1131), as reported by [dp87](https://github.com/dp87)
- [Issue 1129](https://github.com/firefly-iii/firefly-iii/issues/1129), as reported by [gavu](https://github.com/gavu)
- [Issue 1132](https://github.com/firefly-iii/firefly-iii/issues/1132), as reported by [gavu](https://github.com/gavu)
- Issue with cache in Sandstorm ([issue 1130](https://github.com/firefly-iii/firefly-iii/issues/1130))
- [Issue 1134](https://github.com/firefly-iii/firefly-iii/issues/1134)
- [Issue 1140](https://github.com/firefly-iii/firefly-iii/issues/1140)
- [Issue 1141](https://github.com/firefly-iii/firefly-iii/issues/1141), reported by [ErikFontanel](https://github.com/ErikFontanel)
- [Issue 1142](https://github.com/firefly-iii/firefly-iii/issues/1142)

### Security
- Removed many access rights from the demo user

## [4.6.13] - 2018-01-06
### Added
- [Issue 1074](https://github.com/firefly-iii/firefly-iii/issues/1074), suggested by [MacPaille](https://github.com/MacPaille)
- [Issue 1077](https://github.com/firefly-iii/firefly-iii/issues/1077), suggested by [wtercato](https://github.com/wtercato)
- Bulk edit of transactions thanks to [vicmosin](https://github.com/vicmosin) ([issue 1078](https://github.com/firefly-iii/firefly-iii/issues/1078))
- Support for Turkish.
- [Issue 1090](https://github.com/firefly-iii/firefly-iii/issues/1090), suggested by [Findus23](https://github.com/Findus23)
- [Issue 1097](https://github.com/firefly-iii/firefly-iii/issues/1097), suggested by [kelvinhammond](https://github.com/kelvinhammond)
- [Issue 1093](https://github.com/firefly-iii/firefly-iii/issues/1093), suggested by [jinformatique](https://github.com/jinformatique)
- [Issue 1098](https://github.com/firefly-iii/firefly-iii/issues/1098), suggested by [Nik-vr](https://github.com/Nik-vr)

### Fixed
- [Issue 972](https://github.com/firefly-iii/firefly-iii/issues/972), reported by [pjotrvdh](https://github.com/pjotrvdh)
- [Issue 1079](https://github.com/firefly-iii/firefly-iii/issues/1079), reported by [gavu](https://github.com/gavu)
- [Issue 1080](https://github.com/firefly-iii/firefly-iii/issues/1080), reported by [zjean](https://github.com/zjean)
- [Issue 1083](https://github.com/firefly-iii/firefly-iii/issues/1083), reported by [skuzzle](https://github.com/skuzzle)
- [Issue 1085](https://github.com/firefly-iii/firefly-iii/issues/1085), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 1087](https://github.com/firefly-iii/firefly-iii/issues/1087), reported by [4oo4](https://github.com/4oo4)
- [Issue 1089](https://github.com/firefly-iii/firefly-iii/issues/1089), reported by [robin5210](https://github.com/robin5210)
- [Issue 1092](https://github.com/firefly-iii/firefly-iii/issues/1092), reported by [kelvinhammond](https://github.com/kelvinhammond)
- [Issue 1096](https://github.com/firefly-iii/firefly-iii/issues/1096), reported by [wtercato](https://github.com/wtercato)

## [4.6.12] - 2017-12-31
### Added
- Support for Indonesian.
- New report, see [issue 384](https://github.com/firefly-iii/firefly-iii/issues/384)
- [Issue 964](https://github.com/firefly-iii/firefly-iii/issues/964) as suggested by [gavu](https://github.com/gavu)

### Changed
- Greatly improved Docker support and documentation.

### Fixed
- [Issue 1046](https://github.com/firefly-iii/firefly-iii/issues/1046), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1047](https://github.com/firefly-iii/firefly-iii/issues/1047), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1048](https://github.com/firefly-iii/firefly-iii/issues/1048), as reported by [webence](https://github.com/webence)
- [Issue 1049](https://github.com/firefly-iii/firefly-iii/issues/1049), as reported by [nicoschreiner](https://github.com/nicoschreiner) 
- [Issue 1015](https://github.com/firefly-iii/firefly-iii/issues/1015), as reported by a user on Tweakers.net
- [Issue 1056](https://github.com/firefly-iii/firefly-iii/issues/1056), as reported by [repercussion](https://github.com/repercussion) 
- [Issue 1061](https://github.com/firefly-iii/firefly-iii/issues/1061), as reported by [Meizikyn](https://github.com/Meizikyn)
- [Issue 1045](https://github.com/firefly-iii/firefly-iii/issues/1045), as reported by [gavu](https://github.com/gavu)
- First code for [issue 1040](https://github.com/firefly-iii/firefly-iii/issues/1040) ([simonsmiley](https://github.com/simonsmiley))
- [Issue 1059](https://github.com/firefly-iii/firefly-iii/issues/1059), as reported by [4oo4](https://github.com/4oo4)
- [Issue 1063](https://github.com/firefly-iii/firefly-iii/issues/1063), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1064](https://github.com/firefly-iii/firefly-iii/issues/1064), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1066](https://github.com/firefly-iii/firefly-iii/issues/1066), reported by [wtercato](https://github.com/wtercato)


## [4.6.11.1] - 2017-12-08
### Added
- Import routine can scan for matching bills, [issue 956](https://github.com/firefly-iii/firefly-iii/issues/956)

### Changed
- Import will no longer scan for rules, this has become optional. Originally suggested in [issue 956](https://github.com/firefly-iii/firefly-iii/issues/956) by [gavu](https://github.com/gavu) 
- [Issue 1033](https://github.com/firefly-iii/firefly-iii/issues/1033), as reported by [Jumanjii](https://github.com/Jumanjii)
- [Issue 1033](https://github.com/firefly-iii/firefly-iii/issues/1034), as reported by [Aquariu](https://github.com/Aquariu)
- Extra admin check for [issue 1039](https://github.com/firefly-iii/firefly-iii/issues/1039), as reported by [ocdtrekkie](https://github.com/ocdtrekkie)

### Fixed
- Missing translations ([issue 1026](https://github.com/firefly-iii/firefly-iii/issues/1026)), as reported by [gavu](https://github.com/gavu) and [zjean](https://github.com/zjean)
- [Issue 1028](https://github.com/firefly-iii/firefly-iii/issues/1028), reported by [zjean](https://github.com/zjean)
- [Issue 1029](https://github.com/firefly-iii/firefly-iii/issues/1029), reported by [zjean](https://github.com/zjean)
- [Issue 1030](https://github.com/firefly-iii/firefly-iii/issues/1030), as reported by [Traxxi](https://github.com/Traxxi)
- [Issue 1036](https://github.com/firefly-iii/firefly-iii/issues/1036), as reported by [webence](https://github.com/webence)
- [Issue 1038](https://github.com/firefly-iii/firefly-iii/issues/1038), as reported by [gavu](https://github.com/gavu)

## [4.6.11] - 2017-11-30
### Added
- A debug page at `/debug` for easier debug.
- Strings translatable (see [issue 976](https://github.com/firefly-iii/firefly-iii/issues/976)), thanks to [Findus23](https://github.com/Findus23)
- Even more strings are translatable (and translated), thanks to [pkoziol](https://github.com/pkoziol) (see [issue 979](https://github.com/firefly-iii/firefly-iii/issues/979))
- Reconciliation of accounts ([issue 736](https://github.com/firefly-iii/firefly-iii/issues/736)), as requested by [kristophr](https://github.com/kristophr) and several others

### Changed
- Extended currency list, as suggested by [emuhendis](https://github.com/emuhendis) in [issue 994](https://github.com/firefly-iii/firefly-iii/issues/994)
- [Issue 996](https://github.com/firefly-iii/firefly-iii/issues/996) as suggested by [dp87](https://github.com/dp87)

### Removed
- Disabled Heroku support until I get it working again.

### Fixed
- [Issue 980](https://github.com/firefly-iii/firefly-iii/issues/980), reported by [Tim-Frensch](https://github.com/Tim-Frensch)
- [Issue 987](https://github.com/firefly-iii/firefly-iii/issues/987), reported by [gavu](https://github.com/gavu)
- [Issue 988](https://github.com/firefly-iii/firefly-iii/issues/988), reported by [gavu](https://github.com/gavu)
- [Issue 992](https://github.com/firefly-iii/firefly-iii/issues/992), reported by [ncicovic](https://github.com/ncicovic)
- [Issue 993](https://github.com/firefly-iii/firefly-iii/issues/993), reported by [gavu](https://github.com/gavu)
- [Issue 997](https://github.com/firefly-iii/firefly-iii/issues/997), reported by [gavu](https://github.com/gavu)
- [Issue 1000](https://github.com/firefly-iii/firefly-iii/issues/1000), reported by [xpfgsyb](https://github.com/xpfgsyb)
- [Issue 1001](https://github.com/firefly-iii/firefly-iii/issues/1001), reported by [gavu](https://github.com/gavu)
- [Issue 1002](https://github.com/firefly-iii/firefly-iii/issues/1002), reported by [ursweiss](https://github.com/ursweiss)
- [Issue 1003](https://github.com/firefly-iii/firefly-iii/issues/1003), reported by [ursweiss](https://github.com/ursweiss)
- [Issue 1004](https://github.com/firefly-iii/firefly-iii/issues/1004), reported by [Aquariu](https://github.com/Aquariu)
- [Issue 1010](https://github.com/firefly-iii/firefly-iii/issues/1010)
- [Issue 1014](https://github.com/firefly-iii/firefly-iii/issues/1014), reported by [ursweiss](https://github.com/ursweiss)
- [Issue 1016](https://github.com/firefly-iii/firefly-iii/issues/1016)
- [Issue 1024](https://github.com/firefly-iii/firefly-iii/issues/1024), reported by [gavu](https://github.com/gavu)
- [Issue 1025](https://github.com/firefly-iii/firefly-iii/issues/1025), reported by [gavu](https://github.com/gavu)


## [4.6.10] - 2017-11-03
### Added
- Greatly expanded Docker support thanks to [alazare619](https://github.com/alazare619)
- [Issue 967](https://github.com/firefly-iii/firefly-iii/issues/967), thanks to [Aquariu](https://github.com/Aquariu)

### Changed
- Improved Sandstorm support.

### Fixed
- [Issue 963](https://github.com/firefly-iii/firefly-iii/issues/963), as reported by [gavu](https://github.com/gavu)
- [Issue 970](https://github.com/firefly-iii/firefly-iii/issues/970), as reported by [gavu](https://github.com/gavu)
- [Issue 971](https://github.com/firefly-iii/firefly-iii/issues/971), as reported by [gavu](https://github.com/gavu)
- Various Sandstorm.io related issues.

## [4.6.9] - 2017-10-22
### Added
- Firefly III is now available on the [Sandstorm.io](https://apps.sandstorm.io/app/uws252ya9mep4t77tevn85333xzsgrpgth8q4y1rhknn1hammw70) market.
- Issue template
- Pull request template
- Clean up routine to remove double budget limits (see [issue 932](https://github.com/firefly-iii/firefly-iii/issues/932))

### Changed
- Changed license to GPLv3.

### Fixed
- [Issue 895](https://github.com/firefly-iii/firefly-iii/issues/895), as reported by [gavu](https://github.com/gavu)
- [Issue 902](https://github.com/firefly-iii/firefly-iii/issues/902), as reported by [gavu](https://github.com/gavu)
- [Issue 916](https://github.com/firefly-iii/firefly-iii/issues/916), as reported by [gavu](https://github.com/gavu)
- [Issue 942](https://github.com/firefly-iii/firefly-iii/issues/942), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 943](https://github.com/firefly-iii/firefly-iii/issues/943), as reported by [aclex](https://github.com/aclex)
- [Issue 944](https://github.com/firefly-iii/firefly-iii/issues/944), as reported by [gavu](https://github.com/gavu)
- [Issue 932](https://github.com/firefly-iii/firefly-iii/issues/932), as reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 933](https://github.com/firefly-iii/firefly-iii/issues/933), as reported by [nicoschreiner](https://github.com/nicoschreiner)

## [4.6.8] - 2017-10-15
### Added
- Verify routine will check if deposits have a budget (they shouldn't).
- New translations!

### Changed
- Changed docker files for [issue 919](https://github.com/firefly-iii/firefly-iii/issues/919) and [issue 915](https://github.com/firefly-iii/firefly-iii/issues/915)

### Fixed
- [Issue 917](https://github.com/firefly-iii/firefly-iii/issues/917), as reported by [Wr0ngName](https://github.com/Wr0ngName)
- Rules can no longer set a budget for a transfer or a deposit ([issue 916](https://github.com/firefly-iii/firefly-iii/issues/916))
- Fixed [issue 925](https://github.com/firefly-iii/firefly-iii/issues/925), [issue 928](https://github.com/firefly-iii/firefly-iii/issues/928) as reported by [dzaikos](https://github.com/dzaikos) and [DeltaKiloz](https://github.com/DeltaKiloz)
- A fix for [issue 926](https://github.com/firefly-iii/firefly-iii/issues/926), as reported by [Aquariu](https://github.com/Aquariu)

## [4.6.7] - 2017-10-09
### Added
- [Issue 872](https://github.com/firefly-iii/firefly-iii/issues/872), reported [gavu](https://github.com/gavu)

### Fixed
- [Issue 878](https://github.com/firefly-iii/firefly-iii/issues/878), fixed by [Findus23](https://github.com/Findus23)
- [Issue 881](https://github.com/firefly-iii/firefly-iii/issues/881), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 884](https://github.com/firefly-iii/firefly-iii/issues/884), by [gavu](https://github.com/gavu)
- [Issue 840](https://github.com/firefly-iii/firefly-iii/issues/840), reported by [MacPaille](https://github.com/MacPaille)
- [Issue 882](https://github.com/firefly-iii/firefly-iii/issues/882), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 891](https://github.com/firefly-iii/firefly-iii/issues/891), [issue 892](https://github.com/firefly-iii/firefly-iii/issues/892), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 891](https://github.com/firefly-iii/firefly-iii/issues/891), reported by [gavu](https://github.com/gavu)
- [Issue 827](https://github.com/firefly-iii/firefly-iii/issues/827), fixed by [pkoziol](https://github.com/pkoziol)
- [Issue 903](https://github.com/firefly-iii/firefly-iii/issues/903), fixed by [hduijn](https://github.com/hduijn)
- [Issue 904](https://github.com/firefly-iii/firefly-iii/issues/904), reported by [gavu](https://github.com/gavu)
- [Issue 910](https://github.com/firefly-iii/firefly-iii/issues/910), reported by [gavu](https://github.com/gavu)
- [Issue 911](https://github.com/firefly-iii/firefly-iii/issues/911), reported by [gavu](https://github.com/gavu)
- [Issue 915](https://github.com/firefly-iii/firefly-iii/issues/915), reported by [TomWis97](https://github.com/TomWis97)
- [Issue 917](https://github.com/firefly-iii/firefly-iii/issues/917), reported by [Wr0ngName](https://github.com/Wr0ngName)

## [4.6.6] - 2017-09-30
### Added
- [Issue 826](https://github.com/firefly-iii/firefly-iii/issues/826), reported by [pkoziol](https://github.com/pkoziol).
- [Issue 855](https://github.com/firefly-iii/firefly-iii/issues/855), by [ms32035](https://github.com/ms32035)
- [Issue 786](https://github.com/firefly-iii/firefly-iii/issues/786), by [SmilingWorlock](https://github.com/SmilingWorlock)
- [Issue 875](https://github.com/firefly-iii/firefly-iii/issues/875), by [gavu](https://github.com/gavu)
- [Issue 834](https://github.com/firefly-iii/firefly-iii/issues/834), by [gavu](https://github.com/gavu) (and others)


### Changed
- Upgraded to Laravel 5.5
- Add version parameter to CSS and JS files
- [Issue 823](https://github.com/firefly-iii/firefly-iii/issues/823), [issue 824](https://github.com/firefly-iii/firefly-iii/issues/824) fixed Docker config by [DieBauer](https://github.com/DieBauer)

### Fixed
- [Issue 830](https://github.com/firefly-iii/firefly-iii/issues/830)
- [Issue 822](https://github.com/firefly-iii/firefly-iii/issues/822), reported by [gazben](https://github.com/gazben)
- [Issue 827](https://github.com/firefly-iii/firefly-iii/issues/827), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 835](https://github.com/firefly-iii/firefly-iii/issues/835), reported by [gavu](https://github.com/gavu)
- [Issue 836](https://github.com/firefly-iii/firefly-iii/issues/836), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 838](https://github.com/firefly-iii/firefly-iii/issues/838), reported by [gavu](https://github.com/gavu)
- [Issue 839](https://github.com/firefly-iii/firefly-iii/issues/839), reported by [gavu](https://github.com/gavu)
- [Issue 843](https://github.com/firefly-iii/firefly-iii/issues/843), reported by [gavu](https://github.com/gavu)
- [Issue 837](https://github.com/firefly-iii/firefly-iii/issues/837), reported by [gavu](https://github.com/gavu)
- [Issue 845](https://github.com/firefly-iii/firefly-iii/issues/845), reported by [gavu](https://github.com/gavu)
- [Issue 846](https://github.com/firefly-iii/firefly-iii/issues/846), reported by [gavu](https://github.com/gavu)
- [Issue 848](https://github.com/firefly-iii/firefly-iii/issues/848), reported by [gavu](https://github.com/gavu)
- [Issue 854](https://github.com/firefly-iii/firefly-iii/issues/854), reported by [gavu](https://github.com/gavu)
- [Issue 866](https://github.com/firefly-iii/firefly-iii/issues/866), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 847](https://github.com/firefly-iii/firefly-iii/issues/847), reported by [gavu](https://github.com/gavu)
- [Issue 853](https://github.com/firefly-iii/firefly-iii/issues/853), reported by [gavu](https://github.com/gavu)
- [Issue 857](https://github.com/firefly-iii/firefly-iii/issues/857), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 865](https://github.com/firefly-iii/firefly-iii/issues/865), reported by [simonsmiley](https://github.com/simonsmiley)
- [Issue 826](https://github.com/firefly-iii/firefly-iii/issues/826), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 856](https://github.com/firefly-iii/firefly-iii/issues/856), reported by [ms32035](https://github.com/ms32035)
- [Issue 860](https://github.com/firefly-iii/firefly-iii/issues/860), reported by [gavu](https://github.com/gavu)
- [Issue 861](https://github.com/firefly-iii/firefly-iii/issues/861), reported by [gavu](https://github.com/gavu)
- [Issue 870](https://github.com/firefly-iii/firefly-iii/issues/870), reported by [gavu](https://github.com/gavu)

## [4.6.5] - 2017-09-09

### Added
- [Issue 616](https://github.com/firefly-iii/firefly-iii/issues/616), The ability to link transactions
- [Issue 763](https://github.com/firefly-iii/firefly-iii/issues/763), as suggested by [tannie](https://github.com/tannie)
- [Issue 770](https://github.com/firefly-iii/firefly-iii/issues/770), as suggested by [skibbipl](https://github.com/skibbipl)
- [Issue 780](https://github.com/firefly-iii/firefly-iii/issues/780), as suggested by [skibbipl](https://github.com/skibbipl)
- [Issue 784](https://github.com/firefly-iii/firefly-iii/issues/784), as suggested by [SmilingWorlock](https://github.com/SmilingWorlock)
- Lots of code for future support of automated Bunq imports

### Changed
- Rewrote the export routine
- [Issue 782](https://github.com/firefly-iii/firefly-iii/issues/782), as suggested by [NiceGuyIT](https://github.com/NiceGuyIT)
- [Issue 800](https://github.com/firefly-iii/firefly-iii/issues/800), as suggested by [jleeong](https://github.com/jleeong)

### Fixed
- [Issue 724](https://github.com/firefly-iii/firefly-iii/issues/724), reported by [skibbipl](https://github.com/skibbipl)
- [Issue 738](https://github.com/firefly-iii/firefly-iii/issues/738), reported by [skibbipl](https://github.com/skibbipl)
- [Issue 760](https://github.com/firefly-iii/firefly-iii/issues/760), reported by [leander091](https://github.com/leander091)
- [Issue 764](https://github.com/firefly-iii/firefly-iii/issues/764), reported by [tannie](https://github.com/tannie)
- [Issue 792](https://github.com/firefly-iii/firefly-iii/issues/792), reported by [jleeong](https://github.com/jleeong)
- [Issue 793](https://github.com/firefly-iii/firefly-iii/issues/793), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 797](https://github.com/firefly-iii/firefly-iii/issues/797), reported by [leander091](https://github.com/leander091)
- [Issue 801](https://github.com/firefly-iii/firefly-iii/issues/801), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 803](https://github.com/firefly-iii/firefly-iii/issues/803), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 805](https://github.com/firefly-iii/firefly-iii/issues/805), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 806](https://github.com/firefly-iii/firefly-iii/issues/806), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 807](https://github.com/firefly-iii/firefly-iii/issues/807), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 808](https://github.com/firefly-iii/firefly-iii/issues/808), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 809](https://github.com/firefly-iii/firefly-iii/issues/809), reported by [pkoziol](https://github.com/pkoziol)
- [Issue 814](https://github.com/firefly-iii/firefly-iii/issues/814), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 818](https://github.com/firefly-iii/firefly-iii/issues/818), reported by [gavu](https://github.com/gavu)
- [Issue 819](https://github.com/firefly-iii/firefly-iii/issues/819), reported by [DieBauer](https://github.com/DieBauer)
- [Issue 820](https://github.com/firefly-iii/firefly-iii/issues/820), reported by [simonsmiley](https://github.com/simonsmiley) 
- Various other fixes


## [4.6.4] - 2017-08-13
### Added
- PHP7.1 support
- Routine to decrypt attachments from the command line, for [issue 671](https://github.com/firefly-iii/firefly-iii/issues/671)
- A routine that can check if your password has been stolen in the past.
- Split transaction shows amount left to be split


### Changed
- Importer can (potentially) handle new import routines such as banks.
- Importer can fall back from JSON errors 

### Removed
- PHP7.0 support
- Support for extended tag modes
- Remove "time jumps" to non-empty periods


### Fixed
- [Issue 717](https://github.com/firefly-iii/firefly-iii/issues/717), reported by [NiceGuyIT](https://github.com/NiceGuyIT)
- [Issue 718](https://github.com/firefly-iii/firefly-iii/issues/718), reported by [wtercato](https://github.com/wtercato)
- [Issue 722](https://github.com/firefly-iii/firefly-iii/issues/722), reported by [simonsmiley](https://github.com/simonsmiley)
- [Issue 648](https://github.com/firefly-iii/firefly-iii/issues/648), reported by [skibbipl](https://github.com/skibbipl)
- [Issue 730](https://github.com/firefly-iii/firefly-iii/issues/730), reported by [ragnarkarlsson](https://github.com/ragnarkarlsson)
- [Issue 733](https://github.com/firefly-iii/firefly-iii/issues/733), reported by [xpfgsyb](https://github.com/xpfgsyb)
- [Issue 735](https://github.com/firefly-iii/firefly-iii/issues/735), reported by [kristophr](https://github.com/kristophr)
- [Issue 739](https://github.com/firefly-iii/firefly-iii/issues/739), reported by [skibbipl](https://github.com/skibbipl)
- [Issue 515](https://github.com/firefly-iii/firefly-iii/issues/515), reported by [schwalberich](https://github.com/schwalberich)
- [Issue 743](https://github.com/firefly-iii/firefly-iii/issues/743), reported by [simonsmiley](https://github.com/simonsmiley)
- [Issue 746](https://github.com/firefly-iii/firefly-iii/issues/746), reported by [tannie](https://github.com/tannie)
- [Issue 747](https://github.com/firefly-iii/firefly-iii/issues/747), reported by [tannie](https://github.com/tannie)


## [4.6.3.1] - 2017-07-23
### Fixed
- Hotfix to close [issue 715](https://github.com/firefly-iii/firefly-iii/issues/715)

## [4.6.3] - 2017-07-23

This will be the last release to support PHP 7.0.

### Added
- New guidelines and new introduction tour to aid new users.
- Rules can now be applied at will to transactions, not just rule groups.

### Changed
- Improved category overview.
- Improved budget overview.
- Improved budget report.
- Improved command line import responsiveness and speed.
- All code comparisons are now strict.
- Improve search page.
- Charts are easier to read thanks to [simonsmiley](https://github.com/simonsmiley)
- Fixed [issue 708](https://github.com/firefly-iii/firefly-iii/issues/708).

### Fixed
- Fixed bug where import would not respect default account. [issue 694](https://github.com/firefly-iii/firefly-iii/issues/694)
- Fixed various broken paths
- Fixed several import inconsistencies.
- Various bug fixes.

## [4.6.2] - 2017-07-08
### Added
- Links added to boxes, idea by [simonsmiley](https://github.com/simonsmiley)

### Fixed
- Various bugs in import routine

## [4.6.1] - 2017-07-02
### Fixed
- Fixed several small issues all around.

## [4.6.0] - 2017-06-28

### Changed
- Revamped import routine. Will be buggy.

### Fixed
- [Issue 667](https://github.com/firefly-iii/firefly-iii/issues/667), postgresql reported by [skibbipl](https://github.com/skibbipl).
- [Issue 680](https://github.com/firefly-iii/firefly-iii/issues/680), fixed by [Xeli](https://github.com/Xeli)
- Fixed [issue 660](https://github.com/firefly-iii/firefly-iii/issues/660)
- Fixes [issue 672](https://github.com/firefly-iii/firefly-iii/issues/672), reported by [dzaikos](https://github.com/dzaikos)
- Fix a bug where the balance routine forgot to account for accounts without a currency preference.
- Various other bugfixes.

## [4.5.0] - 2017-06-07

### Added
- Better support for multi-currency transactions and display of transactions, accounts and everything. This requires a database overhaul (moving the currency information to specific transactions) so be careful when upgrading.
- Translations for Spanish and Slovenian.
- New interface for budget page, ~~stolen from~~ inspired by YNAB.
- Expanded Docker to work with postgresql as well, thanks to [kressh](https://github.com/kressh)

### Fixed
- PostgreSQL support in database upgrade routine ([issue 644](https://github.com/firefly-iii/firefly-iii/issues/644), reported by [skibbipl](https://github.com/skibbipl))
- Frontpage budget chart was off, fix by [nhaarman](https://github.com/nhaarman)
- Was not possible to remove opening balance.

## [4.4.3] - 2017-05-03
### Added
- Added support for Slovenian
- Removed support for Spanish. No translations whatsoever by the guy who requested it.
- Removed support for Russian. Same thing.
- Removed support for Croatian. Same thing.
- Removed support for Chinese Traditional, Hong Kong. Same thing.

### Changed
- The journal collector, an internal piece of code to collect transactions, now uses a slightly different method of collecting journals. This may cause problems.

### Fixed
- [Issue 638](https://github.com/firefly-iii/firefly-iii/issues/638) as reported by [worldworm](https://github.com/worldworm).
- Possible fix for [issue 624](https://github.com/firefly-iii/firefly-iii/issues/624)

## [4.4.2] - 2017-04-27
### Fixed
- Fixed a bug where the opening balance could not be stored.

## [4.4.1] - 2017-04-27

### Added
- Support for deployment on Heroku

### Fixed
- Bug in new-user routine.

## [4.4.0] - 2017-04-23
### Added
- Firefly III can now handle foreign currencies better, including some code to get the exchange rate live from the web.
- Can now make rules for attachments, see [issue 608](https://github.com/firefly-iii/firefly-iii/issues/608), as suggested by [dzaikos](https://github.com/dzaikos).

### Fixed
- Fixed [issue 629](https://github.com/firefly-iii/firefly-iii/issues/629), reported by [forcaeluz](https://github.com/forcaeluz)
- Fixed [issue 630](https://github.com/firefly-iii/firefly-iii/issues/630), reported by [welbert](https://github.com/welbert)
- And more various bug fixes.

## [4.3.8] - 2017-04-08

### Added
- Better overview / show pages.
- [Issue 628](https://github.com/firefly-iii/firefly-iii/issues/628), as reported by [xzaz](https://github.com/xzaz).
- Greatly expanded test coverage

### Fixed
- [Issue 619](https://github.com/firefly-iii/firefly-iii/issues/619), as reported by [dfiel](https://github.com/dfiel).
- [Issue 620](https://github.com/firefly-iii/firefly-iii/issues/620), as reported by [forcaeluz](https://github.com/forcaeluz).
- Attempt to fix [issue 624](https://github.com/firefly-iii/firefly-iii/issues/624), as reported by [TheSerenin](https://github.com/TheSerenin).
- Favicon link is relative now, fixed by [welbert](https://github.com/welbert).
- Some search bugs

## [4.3.7] - 2017-03-06
### Added
- Nice user friendly views for empty lists.
- Extended contribution guidelines.
- First version of financial report filtered on tags.
- Suggested monthly savings for piggy banks, by [Zsub](https://github.com/Zsub)
- Better test coverage.

### Changed
- Slightly changed tag overview.
- Consistent icon for bill in list.
- Slightly changed account overview.

### Removed
- Removed IDE specific views from .gitignore, [issue 598](https://github.com/firefly-iii/firefly-iii/issues/598)

### Fixed
- Force key generation during installation.
- The `date` function takes the fieldname where a date is stored, not the literal date by [Zsub](https://github.com/Zsub)
- Improved budget frontpage chart, as suggested by [skibbipl](https://github.com/skibbipl)
- [Issue 602](https://github.com/firefly-iii/firefly-iii/issues/602) and [issue 607](https://github.com/firefly-iii/firefly-iii/issues/607), as reported by [skibbipl](https://github.com/skibbipl) and [dzaikos](https://github.com/dzaikos).
- [Issue 605](https://github.com/firefly-iii/firefly-iii/issues/605), as reported by [Zsub](https://github.com/Zsub).
- [Issue 599](https://github.com/firefly-iii/firefly-iii/issues/599), as reported by [leander091](https://github.com/leander091).
- [Issue 610](https://github.com/firefly-iii/firefly-iii/issues/610), as reported by [skibbipl](https://github.com/skibbipl).
- [Issue 611](https://github.com/firefly-iii/firefly-iii/issues/611), as reported by [ragnarkarlsson](https://github.com/ragnarkarlsson).
- [Issue 612](https://github.com/firefly-iii/firefly-iii/issues/612), as reported by [ragnarkarlsson](https://github.com/ragnarkarlsson).
- [Issue 614](https://github.com/firefly-iii/firefly-iii/issues/614), as reported by [worldworm](https://github.com/worldworm).
- Various other bug fixes.

## [4.3.6] - 2017-02-20
### Fixed
- [Issue 578](https://github.com/firefly-iii/firefly-iii/issues/578), reported by [xpfgsyb](https://github.com/xpfgsyb).

## [4.3.5] - 2017-02-19
### Added
- Beta support for Sandstorm.IO
- Docker support by [schoentoon](https://github.com/schoentoon), [elohmeier](https://github.com/elohmeier), [patrickkostjens](https://github.com/patrickkostjens) and [crash7](https://github.com/crash7)!
- Can now use special keywords in the search to search for specic dates, categories, etc.

### Changed
- Updated to laravel 5.4!
- User friendly error message
- Updated locales to support more operating systems, first reported in [issue 536](https://github.com/firefly-iii/firefly-iii/issues/536) by [dabenzel](https://github.com/dabenzel)
- Updated budget report
- Improved 404 page
- Smooth curves, improved by [elamperti](https://github.com/elamperti).

### Fixed
- [Issue 549](https://github.com/firefly-iii/firefly-iii/issues/549)
- [Issue 553](https://github.com/firefly-iii/firefly-iii/issues/553)
- Fixed [issue 559](https://github.com/firefly-iii/firefly-iii/issues/559) reported by [elamperti](https://github.com/elamperti).
- [Issue 565](https://github.com/firefly-iii/firefly-iii/issues/565), as reported by a user over the mail
- [Issue 566](https://github.com/firefly-iii/firefly-iii/issues/566), as reported by [dspeckmann](https://github.com/dspeckmann)
- [Issue 567](https://github.com/firefly-iii/firefly-iii/issues/567), as reported by [winsomniak](https://github.com/winsomniak)
- [Issue 569](https://github.com/firefly-iii/firefly-iii/issues/569), as reported by [winsomniak](https://github.com/winsomniak)
- [Issue 572](https://github.com/firefly-iii/firefly-iii/issues/572), as reported by [zjean](https://github.com/zjean)
- Many issues with the transaction filters which will fix reports (they tended to display the wrong amount).

## [4.3.4] - 2017-02-02
### Fixed
- Fixed bug [issue 550](https://github.com/firefly-iii/firefly-iii/issues/550), reported by [worldworm](https://github.com/worldworm)!
- Fixed bug [issue 551](https://github.com/firefly-iii/firefly-iii/issues/551), reported by [t-me](https://github.com/t-me)!

## [4.3.3] - 2017-01-30

_The 100th release of Firefly!_

### Added
- Add locales to Docker ([issue 534](https://github.com/firefly-iii/firefly-iii/issues/534)) by [elohmeier](https://github.com/elohmeier).
- Optional database encryption. On by default.
- Datepicker for Firefox and other browsers.
- New instruction block for updating and installing.
- Ability to clone transactions.
- Use multi-select Bootstrap thing instead of massive lists of checkboxes.

### Removed
- Lots of old Javascript

### Fixed
- Missing sort broke various charts
- Bug in reports that made amounts behave weird
- Various bug fixes

### Security
- Tested FF against the naughty string list.

## [4.3.2] - 2017-01-09

An intermediate release because something in the Twig and Twigbridge libraries is broken and I have to make sure it doesn't affect you guys. But some cool features were on their way so there's that oo.

### Added
- Some code for [issue 475](https://github.com/firefly-iii/firefly-iii/issues/475), consistent overviews.
- Better currency display. Make sure you have locale packages installed.

### Changed
- Uses a new version of Laravel.

### Fixed
- The password reset routine was broken.
- [Issue 522](https://github.com/firefly-iii/firefly-iii/issues/522), thanks to [xpfgsyb](https://github.com/xpfgsyb)
- [Issue 524](https://github.com/firefly-iii/firefly-iii/issues/524), thanks to [worldworm](https://github.com/worldworm)
- [Issue 526](https://github.com/firefly-iii/firefly-iii/issues/526), thanks to [worldworm](https://github.com/worldworm)
- [Issue 528](https://github.com/firefly-iii/firefly-iii/issues/528), thanks to [skibbipl](https://github.com/skibbipl)
- Various other fixes.

## [4.3.1] - 2017-01-04
### Added
- Support for Russian and Polish. 
- Support for a proper demo website.
- Support for custom decimal places in currencies ([issue 506](https://github.com/firefly-iii/firefly-iii/issues/506), suggested by [xpfgsyb](https://github.com/xpfgsyb)).
- Most amounts are now right-aligned ([issue 511](https://github.com/firefly-iii/firefly-iii/issues/511), suggested by [xpfgsyb](https://github.com/xpfgsyb)).
- German is now a "complete" language, more than 75% translated!

### Changed
- **[New Github repository!](github.com/firefly-iii/firefly-iii)**
- Better category overview.
- [Issue 502](https://github.com/firefly-iii/firefly-iii/issues/502), thanks to [zjean](https://github.com/zjean)

### Removed
- Removed a lot of administration functions.
- Removed ability to activate users.

### Fixed
- [Issue 501](https://github.com/firefly-iii/firefly-iii/issues/501), thanks to [zjean](https://github.com/zjean)
- [Issue 513](https://github.com/firefly-iii/firefly-iii/issues/513), thanks to [skibbipl](https://github.com/skibbipl) 

### Security
- [Issue 519](https://github.com/firefly-iii/firefly-iii/issues/519), thanks to [xpfgsyb](https://github.com/xpfgsyb)

## [4.3.0] - 2016-12-26
### Added
- New method of keeping track of available budget, see [issue 489](https://github.com/firefly-iii/firefly-iii/issues/489)
- Support for Spanish
- Firefly III now has an extended demo mode. Will expand further in the future.
 

### Changed
- New favicon
- Import routine no longer gives transactions a description [issue 483](https://github.com/firefly-iii/firefly-iii/issues/483)


### Removed
- All test data generation code.

### Fixed
- Removed import accounts from search results [issue 478](https://github.com/firefly-iii/firefly-iii/issues/478)
- Redirect after delete will no longer go back to deleted item [issue 477](https://github.com/firefly-iii/firefly-iii/issues/477)
- Cannot math [issue 482](https://github.com/firefly-iii/firefly-iii/issues/482)
- Fixed bug in virtual balance field [issue 479](https://github.com/firefly-iii/firefly-iii/issues/479)

## [4.2.2] - 2016-12-18
### Added
- New budget report (still a bit of a beta)
- Can now edit user

### Changed
- New config for specific events. Still need to build Notifications.

### Fixed
- Various bugs
- [Issue 472](https://github.com/firefly-iii/firefly-iii/issues/472) thanks to [zjean](https://github.com/zjean)

## [4.2.1] - 2016-12-09
### Added
- BIC support (see [issue 430](https://github.com/firefly-iii/firefly-iii/issues/430))
- New category report section and chart (see the general financial report)


### Changed
- Date range picker now also available on mobile devices (see [issue 435](https://github.com/firefly-iii/firefly-iii/issues/435))
- Extended range of amounts for [issue 439](https://github.com/firefly-iii/firefly-iii/issues/439)
- Rewrote all routes. Old bookmarks may break.

## [4.2.0] - 2016-11-27
### Added
- Lots of (empty) tests
- Expanded transaction lists ([issue 377](https://github.com/firefly-iii/firefly-iii/issues/377))
- New charts at account view
- First code for [issue 305](https://github.com/firefly-iii/firefly-iii/issues/305)


### Changed
- Updated all email messages.
- Made some fonts local

### Fixed
- [Issue 408](https://github.com/firefly-iii/firefly-iii/issues/408)
- Various issues with split journals
- [Issue 414](https://github.com/firefly-iii/firefly-iii/issues/414), thx [zjean](https://github.com/zjean)
- [Issue 419](https://github.com/firefly-iii/firefly-iii/issues/419), thx [schwalberich](https://github.com/schwalberich) 
- [Issue 422](https://github.com/firefly-iii/firefly-iii/issues/422), thx [xzaz](https://github.com/xzaz)
- Various import bugs, such as [issue 416](https://github.com/firefly-iii/firefly-iii/issues/416) ([zjean](https://github.com/zjean))

## [4.1.7] - 2016-11-19
### Added
- Check for database table presence in console commands.
- Category report
- Reinstated old test routines.


### Changed
- Confirm account setting is no longer in `.env` file.
- Titles are now in reverse (current page > parent > firefly iii)
- Easier update of language files thanks to Github implementation.
- Uniform colours for charts.

### Fixed
- Made all pages more mobile friendly.
- Fixed [issue 395](https://github.com/firefly-iii/firefly-iii/issues/395) found by [marcoveeneman](https://github.com/marcoveeneman).
- Fixed [issue 398](https://github.com/firefly-iii/firefly-iii/issues/398) found by [marcoveeneman](https://github.com/marcoveeneman).
- Fixed [issue 401](https://github.com/firefly-iii/firefly-iii/issues/401) found by [marcoveeneman](https://github.com/marcoveeneman).
- Many optimizations.
- Updated many libraries.
- Various bugs found by myself.


## [4.1.6] - 2016-11-06
### Added
- New budget table for multi year report.

### Changed
- Greatly expanded help pages and their function.
- Built a new transaction collector, which I think was the idea of [roberthorlings](https://github.com/roberthorlings) originally.
- Rebuilt seach engine.

### Fixed
- [Issue 375](https://github.com/firefly-iii/firefly-iii/issues/375), thanks to [schoentoon](https://github.com/schoentoon) which made it impossible to resurrect currencies.
- [Issue 370](https://github.com/firefly-iii/firefly-iii/issues/370) thanks to [ksmolder](https://github.com/ksmolder)
- [Issue 378](https://github.com/firefly-iii/firefly-iii/issues/378), thanks to [HomelessAvatar](https://github.com/HomelessAvatar)

## [4.1.5] - 2016-11-01
### Changed
- Report parts are loaded using AJAX, making a lot of code more simple.
- Help content will fall back to English.
- Help content is translated through Crowdin.

### Fixed
- [Issue 370](https://github.com/firefly-iii/firefly-iii/issues/370)

## [4.1.4] - 2016-10-30
### Added
- New Dockerfile thanks to [schoentoon](https://github.com/schoentoon)
- Added changing the destination account as rule action.
- Added changing the source account as rule action.
- Can convert transactions into different types.

### Changed
- Changed the export routine to be more future-proof.
- Improved help routine.
- Integrated CrowdIn translations.
- Simplified reports
- Change error message to refer to solution.

### Fixed
- [Issue 367](https://github.com/firefly-iii/firefly-iii/issues/367) thanks to [HungryFeline](https://github.com/HungryFeline)
- [Issue 366](https://github.com/firefly-iii/firefly-iii/issues/366) thanks to [3mz3t](https://github.com/3mz3t)
- [Issue 362](https://github.com/firefly-iii/firefly-iii/issues/362) and [issue 341](https://github.com/firefly-iii/firefly-iii/issues/341) thanks to [bnw](https://github.com/bnw)
- [Issue 355](https://github.com/firefly-iii/firefly-iii/issues/355) thanks to [roberthorlings](https://github.com/roberthorlings)

## [4.1.3] - 2016-10-22
### Fixed
- Some event handlers called the wrong method.

## [4.1.2] - 2016-10-22

### Fixed
- A bug is fixed in the journal event handler that prevented Firefly III from actually storing journals.

## [4.1.1] - 2016-10-22

### Added
- Option to show deposit accounts on the front page.
- Script to upgrade split transactions
- Can now save notes on piggy banks.
- Extend user admin options.
- Run import jobs from the command line


### Changed
- New preferences screen layout.

### Deprecated
- ``firefly:import`` is now ``firefly:start-import``

### Removed
- Lots of old code

### Fixed
- [Issue 357](https://github.com/firefly-iii/firefly-iii/issues/357), where non utf-8 files would break Firefly.
- Tab delimiter is not properly loaded from import configuration ([roberthorlings](https://github.com/roberthorlings))
- System response to yearly bills

## [4.0.2] - 2016-10-14
### Added
- Added ``intl`` dependency to composer file to ease installation (thanks [telyn](https://github.com/telyn))
- Added support for Croatian.

### Changed
- Updated all copyright notices to refer to the [Creative Commons Attribution-ShareAlike 4.0 International License](https://creativecommons.org/licenses/by-sa/4.0/)
- Fixed [issue 344](https://github.com/firefly-iii/firefly-iii/issues/344)
- Fixed [issue 346](https://github.com/firefly-iii/firefly-iii/issues/346), thanks to [SanderKleykens](https://github.com/SanderKleykens)
- [Issue 351](https://github.com/firefly-iii/firefly-iii/issues/351)
- Did some internal remodelling.

### Fixed
- PostgreSQL compatibility thanks to [SanderKleykens](https://github.com/SanderKleykens)
- [roberthorlings](https://github.com/roberthorlings) fixed a bug in the ABN Amro import specific.


## [4.0.1] - 2016-10-04
### Added
- New ING import specific by [tomwerf](https://github.com/tomwerf)
- New Presidents Choice specific to fix [issue 307](https://github.com/firefly-iii/firefly-iii/issues/307)
- Added some trimming ([issue 335](https://github.com/firefly-iii/firefly-iii/issues/335))

### Fixed
- Fixed a bug where incoming transactions would not be properly filtered in several reports.
- [Issue 334](https://github.com/firefly-iii/firefly-iii/issues/334) by [cyberkov](https://github.com/cyberkov)
- [Issue 337](https://github.com/firefly-iii/firefly-iii/issues/337)
- [Issue 336](https://github.com/firefly-iii/firefly-iii/issues/336)
- [Issue 338](https://github.com/firefly-iii/firefly-iii/issues/338) found by [roberthorlings](https://github.com/roberthorlings)

## [4.0.0] - 2016-09-26
### Added
- Upgraded to Laravel 5.3, most other libraries upgraded as well.
- Added GBP as currency, thanks to [Mortalife](https://github.com/Mortalife)

### Changed
- Jump to version 4.0.0.
- Firefly III is now subject to a [Creative Commons Attribution-ShareAlike 4.0 International License](https://creativecommons.org/licenses/by-sa/4.0/) license. Previous versions of this software are still MIT licensed.

### Fixed
- Support for specific decimal places, thanks to [Mortalife](https://github.com/Mortalife)
- Various CSS fixes
- Various bugs, thanks to [fuf](https://github.com/fuf), [sandermulders](https://github.com/sandermulders) and [vissert](https://github.com/vissert)
- Various queries optimized for MySQL 5.7

## [3.10.4] - 2016-09-14
### Fixed
- Migration fix by [sandermulders](https://github.com/sandermulders)
- Tricky import bug fix thanks to [vissert](https://github.com/vissert)
- Currency preference will be correctly pulled from user settings, thanks to [fuf](https://github.com/fuf)
- Simplified code for upgrade instructions.


## [3.10.3] - 2016-08-29
### Added
- More fields for mass-edit, thanks to [vissert](https://github.com/vissert) ([issue 282](https://github.com/firefly-iii/firefly-iii/issues/282))
- First start of German translation

### Changed
- More optional fields for transactions and the ability to filter them.

### Removed
- Preference for budget maximum.

### Fixed
- A bug in the translation routine broke the import.
- It was possible to destroy your Firefly installation by removing all currencies. Thanks [mondjef](https://github.com/mondjef)
- Translation bugs.
- Import bug.

### Security
- Firefly will not accept registrations beyond the first one, by default.


## [3.10.2] - 2016-08-29
### Added
- New Chinese translations. Set Firefly III to show incomplete translations to follow the progress. Want to translate Firefly III in Chinese, or in any other language? Then check out [the Crowdin project](https://crowdin.com/project/firefly-iii).
- Added more admin pages. They do nothing yet.

### Changed
- Import routine will now also apply user rules.
- Various code cleanup.
- Some small HTML changes.

### Fixed
- Bug in the mass edit routines.
- Firefly III over a proxy will now work (see [issue 290](https://github.com/firefly-iii/firefly-iii/issues/290), thanks [dfiel](https://github.com/dfiel) for reporting.
- Sneaky bug in the import routine, fixed by [Bonno](https://github.com/Bonno) 

## [3.10.1] - 2016-08-25
### Added
- More feedback in the import procedure.
- Extended model for import job.
- Web bases import procedure.


### Changed
- Scrutinizer configuration
- Various code clean up.

### Removed
- Code climate YAML file.

### Fixed
- Fixed a bug where a migration would check an empty table name.
- Fixed various bugs in the import routine.
- Fixed various bugs in the piggy banks pages.
- Fixed a bug in the `firefly:verify` routine

## [3.10] - 2016-08-12
### Added
- New charts in year report
- Can add / remove money from piggy bank on mobile device.
- Bill overview shows some useful things.
- Firefly will track registration / activation IP addresses.


### Changed
- Rewrote the import routine.
- The date picker now supports more ranges and periods.
- Rewrote all migrations. [issue 272](https://github.com/firefly-iii/firefly-iii/issues/272)

### Fixed
- [Issue 264](https://github.com/firefly-iii/firefly-iii/issues/264)
- [Issue 265](https://github.com/firefly-iii/firefly-iii/issues/265)
- Fixed amount calculation problems, [issue 266](https://github.com/firefly-iii/firefly-iii/issues/266), thanks [xzaz](https://github.com/xzaz)
- [Issue 271](https://github.com/firefly-iii/firefly-iii/issues/271)
- [Issue 278](https://github.com/firefly-iii/firefly-iii/issues/278), [issue 273](https://github.com/firefly-iii/firefly-iii/issues/273), thanks [StevenReitsma](https://github.com/StevenReitsma) and [rubella](https://github.com/rubella)
- Bug in attachment download routine would report the wrong size to the user's browser.
- Various NULL errors fixed.
- Various strict typing errors fixed.
- Fixed pagination problems, [issue 276](https://github.com/firefly-iii/firefly-iii/issues/276), thanks [xzaz](https://github.com/xzaz)
- Fixed a bug where an expense would be assigned to a piggy bank if you created a transfer first.
- Bulk update problems, [issue 280](https://github.com/firefly-iii/firefly-iii/issues/280), thanks [stickgrinder](https://github.com/stickgrinder)
- Fixed various problems with amount reporting of split transactions.

## [3.9.1] - 2016-06-06
### Fixed
- Fixed a bug where removing money from a piggy bank would not work. See [issue 265](https://github.com/firefly-iii/firefly-iii/issues/265) and [issue 269](https://github.com/firefly-iii/firefly-iii/issues/269)

## [3.9.0] - 2016-05-22
### Added
- [zjean](https://github.com/zjean) has added code that allows you to force "https://"-URL's.
- [tonicospinelli](https://github.com/tonicospinelli) has added Portuguese (Brazil) translations.
- Firefly III supports the *splitting* of transactions:
  - A withdrawal (expense) can be split into multiple sub-transactions (with multiple destinations)
  - Likewise for deposits (incomes). You can set multiple sources.
  - Likewise for transfers.

### Changed
- Update a lot of libraries.
- Big improvement to test data generation.
- Cleaned up many repositories.

### Removed
- Front page boxes will no longer respond to credit card bills.

### Fixed
- Many bugs

## [3.8.4] - 2016-04-24
### Added
- Lots of new translations.
- Can now set page size.
- Can now mass edit transactions.
- Can now mass delete transactions.
- Firefly will now attempt to verify the integrity of your database when updating.

### Changed
- New version of Charts library.

### Fixed
- Several CSV related bugs.
- Several other bugs.
- Bugs fixed by [Bonno](https://github.com/Bonno).

## [3.8.3] - 2016-04-17
### Added
- New audit report to see what happened.

### Changed
- New Chart JS release used.
- Help function is more reliable.

### Fixed
- Expected bill amount is now correct.
- Upgrade will now invalidate cache.
- Search was broken.
- Queries run better

## [3.8.2] - 2016-04-03
### Added
- Small user administration at /admin.
- Informational popups are working in reports.

### Changed
- User activation emails are better

### Fixed
- Some bugs related to accounts and rules.


## [3.8.1] - 2016-03-29
### Added
- More translations
- Extended cookie control.
- User accounts can now be activated (disabled by default).
- Bills can now take the source and destination account name into account.

### Changed
- The pages related to rules have new URL's.

### Fixed
- Spelling errors.
- Problems related to the "account repository".
- Some views showed empty (0.0) amounts.

## [3.8.0] - 2016-03-20
### Added
- Two factor authentication, thanks to the excellent work of [zjean](https://github.com/zjean).
- A new chart showing your net worth in year and multi-year reports.
- You can now see if your current or future rules actually match any transactions, thanks to the excellent work of [roberthorlings](https://github.com/roberthorlings).
- New date fields for transactions. They are not used yet in reports or anything, but they can be filled in.
- New routine to export your data.
- Firefly III will mail the site owner when blocked users try to login, or when blocked domains are used in registrations.


### Changed
- Firefly III now requires PHP 7.0 minimum.


### Fixed
- HTML fixes, thanks to [roberthorlings](https://github.com/roberthorlings) and [zjean](https://github.com/zjean)..
- A bug fix in the ABN Amro importer, thanks to [roberthorlings](https://github.com/roberthorlings)
- It was not possible to change the opening balance, once it had been set. Thanks to [xnyhps](https://github.com/xnyhps) and [marcoveeneman](https://github.com/marcoveeneman) for spotting this.
- Various other bug fixes.



## [3.4.2] - 2015-05-25
### Added
- Initial release.

### Changed
- Initial release.

### Deprecated
- Initial release.

### Removed
- Initial release.

### Fixed
- Initial release.

### Security
- Initial release.
