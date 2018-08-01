<?php

/**
 * import.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    // ALL breadcrumbs and subtitles:
    'index_breadcrumb'                    => 'Importer des données dans Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Prérequis pour la simulation d\'importation',
    'prerequisites_breadcrumb_spectre'    => 'Prérequis pour Spectre',
    'prerequisites_breadcrumb_bunq'       => 'Prérequis pour bunq',
    'prerequisites_breadcrumb_ynab'       => 'Prerequisites for YNAB',
    'job_configuration_breadcrumb'        => 'Configuration pour ":key"',
    'job_status_breadcrumb'               => 'Statut d\'importation pour ":key"',
    'cannot_create_for_provider'          => 'Firefly III ne peut pas créer de tâche pour le fournisseur ":provider".',
    'disabled_for_demo_user'              => 'disabled in demo',

    // index page:
    'general_index_title'                 => 'Importer un fichier',
    'general_index_intro'                 => 'Bienvenue dans la routine d\'importation de Firefly III. Il existe différentes façons d\'importer des données dans Firefly III, affichées ici sous forme de boutons.',
    // import provider strings (index):
    'button_fake'                         => 'Simuler une importation',
    'button_file'                         => 'Importer un fichier',
    'button_bunq'                         => 'Importer depuis bunq',
    'button_spectre'                      => 'Importer en utilisant Spectre',
    'button_plaid'                        => 'Importer en utilisant Plaid',
    'button_yodlee'                       => 'Importer en utilisant Yodlee',
    'button_quovo'                        => 'Importer en utilisant Quovo',
    'button_ynab'                         => 'Import from You Need A Budget',
    // global config box (index)
    'global_config_title'                 => 'Configuration d\'importation globale',
    'global_config_text'                  => 'À l\'avenir, cette boîte contiendra les préférences qui s\'appliquent à TOUTES les sources d\'importation ci-dessus.',
    // prerequisites box (index)
    'need_prereq_title'                   => 'Prérequis d\'importation',
    'need_prereq_intro'                   => 'Certaines méthodes d\'importation nécessitent votre attention avant de pouvoir être utilisées. Par exemple, elles peuvent nécessiter des clés d\'API spéciales ou des clés secrètes. Vous pouvez les configurer ici. L\'icône indique si ces conditions préalables ont été remplies.',
    'do_prereq_fake'                      => 'Prérequis pour la simulation',
    'do_prereq_file'                      => 'Prérequis pour les importations de fichiers',
    'do_prereq_bunq'                      => 'Prérequis pour les importations depuis Bunq',
    'do_prereq_spectre'                   => 'Prérequis pour les importations depuis Spectre',
    'do_prereq_plaid'                     => 'Prérequis pour les importations depuis Plaid',
    'do_prereq_yodlee'                    => 'Prérequis pour les importations depuis Yodlee',
    'do_prereq_quovo'                     => 'Prérequis pour les importations depuis Quovo',
    'do_prereq_ynab'                      => 'Prerequisites for imports from YNAB',

    // provider config box (index)
    'can_config_title'                    => 'Configuration d\'importation',
    'can_config_intro'                    => 'Certaines méthodes d’importation peuvent être configurées selon vos préférences. Elles ont des paramètres supplémentaires que vous pouvez modifier.',
    'do_config_fake'                      => 'Configuration du simulateur d\'importation',
    'do_config_file'                      => 'Configuration pour l’importation de fichier',
    'do_config_bunq'                      => 'Configuration pour les importations depuis Bunq',
    'do_config_spectre'                   => 'Configuration pour les importations depuis Spectre',
    'do_config_plaid'                     => 'Configuration pour les importations depuis Plaid',
    'do_config_yodlee'                    => 'Configuration pour les importations depuis Yodlee',
    'do_config_quovo'                     => 'Configuration pour les importations depuis Quovo',

    // prerequisites:
    'prereq_fake_title'                   => 'Prérequis pour une importation utilisant le simulateur d\'importation',
    'prereq_fake_text'                    => 'Le simulateur d\'importation nécessite une fausse clé d\'API. Vous pouvez utiliser la clé suivante : 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Prérequis à l\'importation de données avec Spectre',
    'prereq_spectre_text'                 => 'Pour importer des données avec l\'API Spectre (v4) vous devez fournir à Firefly III deux secrets. Vous les trouverez sur <a href="https://www.saltedge.com/clients/profile/secrets">la page des secrets</a>.',
    'prereq_spectre_pub'                  => 'De même, l\'API Spectre doit connaitre votre clé publique affichée ci-dessous. Sans elle, vous ne serez pas reconnu. Merci de renseigner votre clé publique dans la <a href="https://www.saltedge.com/clients/profile/secrets">page des secrets</a>.',
    'prereq_bunq_title'                   => 'Prérequis à l\'importation de données depuis bunq',
    'prereq_bunq_text'                    => 'Pour importer des données depuis bunq vous devez obtenir un clé d\'API. Cette clé peut être obtenue depuis l\'application. Merci de prendre en compte que la fonction d\'importation depuis bunq est en BETA. Elle a été testée uniquement au travers de l\'API bac à sable.',
    'prereq_bunq_ip'                      => 'bunq a besoin de votre adresse IP publique. Firefly III a tenté de la déterminer grâce <a href="https://www.ipify.org/">au service ipify</a>. Assurez-vous que l\'adresse IP est correcte ou l\'importation échouera.',
    'prereq_ynab_title'                   => 'Prerequisites for an import from YNAB',
    'prereq_ynab_text'                    => 'In order to be able to download transactions from YNAB, please create a new application on your <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> and enter the client ID and secret on this page.',
    'prereq_ynab_redirect'                => 'To complete the configuration, enter the following URL at the <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> under the "Redirect URI(s)".',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Fausse clé API enregistrée avec succès !',
    'prerequisites_saved_for_spectre'     => 'ID App et secret enregistrés !',
    'prerequisites_saved_for_bunq'        => 'Clé API et adresse IP enregistrées !',
    'prerequisites_saved_for_ynab'        => 'YNAB client ID and secret stored!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Configuration de la tâche - Appliquer vos règles ?',
    'job_config_apply_rules_text'         => 'Une fois le fournisseur de la simulation exécuté, vos règles peuvent être appliquées aux transactions. Notez que ceci allongera le temps de l\'importation.',
    'job_config_input'                    => 'Vos données d\'entrée',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Saisir un nom d\'album',
    'job_config_fake_artist_text'         => 'Beaucoup de routines d\'importation ont quelques étapes de configuration par lesquelles vous devez passer. Dans le cas du fournisseur du simulateur d\'importation, vous devez répondre à des questions étranges. Dans ce cas, saisissez "David Bowie" pour continuer.',
    'job_config_fake_song_title'          => 'Saisir un nom de chanson',
    'job_config_fake_song_text'           => 'Citez la chanson "Golden years" pour continuer la simulation d\'importation.',
    'job_config_fake_album_title'         => 'Saisir un nom d\'album',
    'job_config_fake_album_text'          => 'Certaines routines d\'importation nécessitent des données complémentaires en milieu d\'exécution. Dans le cas du fournisseur du simulateur d\'importation, vous devez répondre à des questions étranges. Saisissez "Station to station" pour continuer.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Configuration de l\'importation (1/4) - Téléchargez votre fichier',
    'job_config_file_upload_text'         => 'Cette routine vous aidera à importer des fichiers depuis votre banque vers Firefly III. ',
    'job_config_file_upload_help'         => 'Choisissez votre fichier. Veuillez vous assurer qu\'il est encodé en UTF-8.',
    'job_config_file_upload_config_help'  => 'Si vous avez précédemment importé des données dans Firefly III, vous avez peut-être téléchargé un fichier de configuration qui définit les relations entre les différents champs. Pour certaines banques, des utilisateurs ont bien voulu partager leur fichier ici : <a href="https://github.com/firefly-iii/import-configurations/wiki">fichiers de configuration</a>',
    'job_config_file_upload_type_help'    => 'Sélectionnez le type de fichier que vous allez télécharger',
    'job_config_file_upload_submit'       => 'Envoyer des fichiers',
    'import_file_type_csv'                => 'CSV (valeurs séparées par des virgules)',
    'file_not_utf8'                       => 'Le fichier téléchargé n\'est pas encodé en UTF-8 ou en ASCII. Firefly ne peut pas gérer un tel fichier. Veuillez utiliser Notepad++ ou Sublime Text pour convertir votre fichier en UTF-8.',
    'job_config_uc_title'                 => 'Configuration de l\'importation (2/4) - Configuration du fichier importé',
    'job_config_uc_text'                  => 'Pour pouvoir importer votre fichier correctement, veuillez valider les options ci-dessous.',
    'job_config_uc_header_help'           => 'Cochez cette case si la première ligne de votre fichier CSV contient les entêtes des colonnes.',
    'job_config_uc_date_help'             => 'Le format de la date et de l’heure dans votre fichier. Suivez les options de formatage décrites sur <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">cette page</a>. La valeur par défaut va analyser les dates ayant cette syntaxe : :dateExample.',
    'job_config_uc_delimiter_help'        => 'Choisissez le délimiteur de champ qui est utilisé dans votre fichier d’entrée. Si vous n\'en êtes pas certain, la virgule est l’option la plus sûre.',
    'job_config_uc_account_help'          => 'Si votre fichier ne contient AUCUNE information concernant vos compte(s) actif, utilisez cette liste déroulante pour choisir à quel compte les opérations contenues dans le fichier s\'appliquent.',
    'job_config_uc_apply_rules_title'     => 'Appliquer les règles',
    'job_config_uc_apply_rules_text'      => 'Appliquer vos règles à chaque opération importée. Notez que cela peut ralentir significativement l\'importation .',
    'job_config_uc_specifics_title'       => 'Options spécifiques à la banque',
    'job_config_uc_specifics_txt'         => 'Certaines banques délivrent des fichiers mal formatés. Firefly III peut les corriger automatiquement. Si votre banque délivre de tels fichiers mais qu\'elle n\'est pas listée ici, merci d\'ouvrir une demande sur GitHub.',
    'job_config_uc_submit'                => 'Continuer',
    'invalid_import_account'              => 'Vous avez sélectionné un compte non valide pour l\'importation.',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Choisissez votre identifiant',
    'job_config_spectre_login_text'       => 'Firefly III a trouvé :count identifiant·s dans votre compte Spectre. Lequel voulez-vous utiliser pour importer des données ?',
    'spectre_login_status_active'         => 'Actif',
    'spectre_login_status_inactive'       => 'Inactif',
    'spectre_login_status_disabled'       => 'Désactivé',
    'spectre_login_new_login'             => 'S\'identifier avec une autre banque, ou à une de ces banques avec un autre identifiant.',
    'job_config_spectre_accounts_title'   => 'Sélectionnez le·s compte·s à importer',
    'job_config_spectre_accounts_text'    => 'Vous avez sélectionné ":name" (:country). Vous avez :count compte·s disponible·s chez ce fournisseur. Veuillez sélectionner le·s compte·s d\'actifs Firefly III dans le·s·quel·s enregistrer les opérations. Souvenez-vous, pour importer des données, le compte Firefly III et le compte ":name" doivent avoir la même devise.',
    'spectre_no_supported_accounts'       => 'Vous ne pouvez pas importer de données depuis ce compte car les devises ne sont pas identiques.',
    'spectre_do_not_import'               => '(ne pas importer)',
    'spectre_no_mapping'                  => 'Il semble que vous n\'avez sélectionné aucun compte depuis lequel importer.',
    'imported_from_account'               => 'Importé depuis ":account"',
    'spectre_account_with_number'         => 'Compte :number',
    'job_config_spectre_apply_rules'      => 'Appliquer les règles',
    'job_config_spectre_apply_rules_text' => 'Par défaut vos règles seront appliquées aux opérations créées pendant l\'importation. Si vous ne voulez pas que vos règles s\'appliquent, décochez cette case.',

    // job configuration for bunq:
    'job_config_bunq_accounts_title'      => 'Comptes bunq',
    'job_config_bunq_accounts_text'       => 'Voici les comptes associés à votre compte bunq. Veuillez sélectionner les comptes depuis lesquels vous voulez importer et le compte vers lequel vous voulez importer les opérations.',
    'bunq_no_mapping'                     => 'Il semble que vous n\'avez sélectionné aucun compte.',
    'should_download_config'              => 'Vous devriez télécharger <a href=":route">le fichier de configuration</a> de cette tâche. Cela rendra vos futures importations plus faciles.',
    'share_config_file'                   => 'Si vous avez importé des données depuis une banque publique, vous devriez <a href="https://github.com/firefly-iii/import-configurations/wiki">partager votre fichier de configuration</a>. Il sera ainsi plus facile pour les autres utilisateurs d\'importer leurs données. Le partage de votre fichier de configuration n\'expose pas vos informations financières.',
    'job_config_bunq_apply_rules'         => 'Appliquer les règles',
    'job_config_bunq_apply_rules_text'    => 'Par défaut vos règles seront appliquées aux opérations créées pendant l\'importation. Si vous ne voulez pas que vos règles s\'appliquent, décochez cette case.',

    'ynab_account_closed'                  => 'Account is closed!',
    'ynab_account_deleted'                 => 'Account is deleted!',
    'ynab_account_type_savings'            => 'savings account',
    'ynab_account_type_checking'           => 'checking account',
    'ynab_account_type_cash'               => 'cash account',
    'ynab_account_type_creditCard'         => 'credit card',
    'ynab_account_type_lineOfCredit'       => 'line of credit',
    'ynab_account_type_otherAsset'         => 'other asset account',
    'ynab_account_type_otherLiability'     => 'other liabilities',
    'ynab_account_type_payPal'             => 'Paypal',
    'ynab_account_type_merchantAccount'    => 'merchant account',
    'ynab_account_type_investmentAccount'  => 'investment account',
    'ynab_account_type_mortgage'           => 'mortgage',
    'ynab_do_not_import'                   => '(do not import)',
    'job_config_ynab_apply_rules'          => 'Apply rules',
    'job_config_ynab_apply_rules_text'     => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Select your budget',
    'job_config_ynab_select_budgets_text'  => 'You have :count budgets stored at YNAB. Please select the one from which Firefly III will import the transactions.',
    'job_config_ynab_no_budgets'           => 'There are no budgets available to be imported from.',
    'ynab_no_mapping'                      => 'It seems you have not selected any accounts to import from.',
    'job_config_ynab_bad_currency'         => 'You cannot import from the following budget(s), because you do not have accounts with the same currency as these budgets.',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Statut',
    'spectre_extra_key_card_type'          => 'Type de carte',
    'spectre_extra_key_account_name'       => 'Nom du compte',
    'spectre_extra_key_client_name'        => 'Nom du client',
    'spectre_extra_key_account_number'     => 'N° de compte',
    'spectre_extra_key_blocked_amount'     => 'Montant bloqué',
    'spectre_extra_key_available_amount'   => 'Montant disponible',
    'spectre_extra_key_credit_limit'       => 'Plafond de crédit',
    'spectre_extra_key_interest_rate'      => 'Taux d\'intérêt',
    'spectre_extra_key_expiry_date'        => 'Date d’expiration',
    'spectre_extra_key_open_date'          => 'Date d\'ouverture',
    'spectre_extra_key_current_time'       => 'Heure actuelle',
    'spectre_extra_key_current_date'       => 'Date actuelle',
    'spectre_extra_key_cards'              => 'Cartes',
    'spectre_extra_key_units'              => 'Unités',
    'spectre_extra_key_unit_price'         => 'Prix unitaire',
    'spectre_extra_key_transactions_count' => 'Nombre de transactions',

    // specifics:
    'specific_ing_name'                    => 'ING NL',
    'specific_ing_descr'                   => 'Créer de meilleures descriptions dans les exports ING',
    'specific_sns_name'                    => 'SNS / Volksbank NL',
    'specific_sns_descr'                   => 'Supprime les guillemets des fichiers SNS / Volksbank',
    'specific_abn_name'                    => 'ABN AMRO NL',
    'specific_abn_descr'                   => 'Corrige d\'éventuels problèmes avec les fichiers ABN AMRO',
    'specific_rabo_name'                   => 'Rabobank NL',
    'specific_rabo_descr'                  => 'Corrige d\'éventuels problèmes avec les fichiers Rabobank',
    'specific_pres_name'                   => 'President\'s Choice Financial CA',
    'specific_pres_descr'                  => 'Corrige d\'éventuels problèmes avec les fichiers PC',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'               => 'Configuration de l\'importation (3/4) - Définir le rôle de chaque colonne',
    'job_config_roles_text'                => 'Chaque colonne de votre fichier CSV contient des données différentes. Veuillez indiquer quel type de données l’importateur doit attendre. L’option de « mapper » les données signifie que vous allez lier chaque entrée trouvée dans la colonne à une valeur dans votre base de données. Une colonne souvent mappée est celle contenant l\'IBAN du compte opposé. Il est facile de le faire correspondre avec un IBAN déjà présent dans votre base de données.',
    'job_config_roles_submit'              => 'Continuer',
    'job_config_roles_column_name'         => 'Nom de colonne',
    'job_config_roles_column_example'      => 'Données d’exemple de colonne',
    'job_config_roles_column_role'         => 'Signification des données de colonne',
    'job_config_roles_do_map_value'        => 'Mapper ces valeurs',
    'job_config_roles_no_example'          => 'Aucun exemple de données disponible',
    'job_config_roles_fa_warning'          => 'Si vous marquez une colonne comme contenant un montant dans une devise étrangère, vous devez également indiquer quelle colonne définie cette devise.',
    'job_config_roles_rwarning'            => 'Vous devez à minima marquer une colonne en tant que colonne "Montant". Il est également conseillé de sélectionner une colonne pour la description, la date et le compte opposé.',
    'job_config_roles_colum_count'         => 'Colonne',
    // job config for the file provider (stage: mapping):
    'job_config_map_title'                 => 'Configuration de l\'importation (4/4) - Connecter les données à importer aux données de Firefly III',
    'job_config_map_text'                  => 'Dans les tableaux suivants, la valeur située à gauche vous montre l\'information trouvée dans votre fichier téléchargé. C’est à vous de mapper cette valeur, si possible, avec une valeur déjà présente dans votre base de données. Firefly III s’en tiendra à ce mappage. Si il n’y a pas de valeur correspondante ou que vous ne souhaitez pas mapper de valeur spécifique, ne sélectionnez rien.',
    'job_config_map_nothing'               => 'Il n\'y a aucun donnée dans votre fichier qui puisse être mappée aux valeurs existantes. Merci de cliquer sur "Démarrez l\'importation" pour continuer.',
    'job_config_field_value'               => 'Valeur du champ',
    'job_config_field_mapped'              => 'Mappé à',
    'map_do_not_map'                       => '(ne pas mapper)',
    'job_config_map_submit'                => 'Démarrez l\'importation',


    // import status page:
    'import_with_key'                      => 'Importer avec la clé \':key\'',
    'status_wait_title'                    => 'Veuillez patienter...',
    'status_wait_text'                     => 'Cette boîte disparaîtra dans un instant.',
    'status_running_title'                 => 'L\'importation est en cours d\'exécution',
    'status_job_running'                   => 'Veuillez patienter, importation des données en cours...',
    'status_job_storing'                   => 'Veuillez patientez, enregistrement des données en cours...',
    'status_job_rules'                     => 'Veuillez patienter, exécution des règles...',
    'status_fatal_title'                   => 'Erreur fatale',
    'status_fatal_text'                    => 'L\'importation a rencontré une erreur qui l\'a empêché de s\'achever correctement. Toutes nos excuses !',
    'status_fatal_more'                    => 'Ce message d\'erreur (probablement très énigmatique) est complété par les fichiers de log que vous trouverez sur votre disque dur ou dans le container Docker depuis lequel vous exécutez Firefly III.',
    'status_finished_title'                => 'Importation terminée',
    'status_finished_text'                 => 'L\'importation est terminée.',
    'finished_with_errors'                 => 'Des erreurs se sont produites pendant l\'importation. Veuillez les examiner avec attention.',
    'unknown_import_result'                => 'Résultat de l\'importation inconnu',
    'result_no_transactions'               => 'Aucune opération n\'a été importée. Il s\'agissait peut être de doublons, ou il n\'y avait simplement aucune opération a importer. Le fichier de log pourra peut être vous en dire plus sur ce qu\'il s\'est passé. Si vous importez des données régulièrement, ceci est normal.',
    'result_one_transaction'               => 'Une seule transaction a été importée. Elle est stockée sous le tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> où vous pouvez l\'afficher en détail.',
    'result_many_transactions'             => 'Firefly III a importé :count transactions. Elles sont stockées sous le tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> où vous pouvez les afficher en détail.',


    // general errors and warnings:
    'bad_job_status'                       => 'Vous ne pouvez pas accéder à cette page tant que l\'importation a le statut ":status".',

    // column roles for CSV import:
    'column__ignore'                       => '(ignorer cette colonne)',
    'column_account-iban'                  => 'Compte d’actif (IBAN)',
    'column_account-id'                    => 'Compte d\'actif (ID correspondant à FF3)',
    'column_account-name'                  => 'Compte d’actif (nom)',
    'column_account-bic'                   => 'Compte d’actif (BIC)',
    'column_amount'                        => 'Montant',
    'column_amount_foreign'                => 'Montant (en devise étrangère)',
    'column_amount_debit'                  => 'Montant (colonne débit)',
    'column_amount_credit'                 => 'Montant (colonne de crédit)',
    'column_amount-comma-separated'        => 'Montant (virgule comme séparateur décimal)',
    'column_bill-id'                       => 'Facture (ID correspondant à FF3)',
    'column_bill-name'                     => 'Nom de la facture',
    'column_budget-id'                     => 'Budget (ID correspondant à FF3)',
    'column_budget-name'                   => 'Nom du budget',
    'column_category-id'                   => 'Catégorie (ID correspondant à FF3)',
    'column_category-name'                 => 'Nom de catégorie',
    'column_currency-code'                 => 'Code de la devise (ISO 4217)',
    'column_foreign-currency-code'         => 'Code de devise étrangère (ISO 4217)',
    'column_currency-id'                   => 'Devise (ID correspondant à FF3)',
    'column_currency-name'                 => 'Nom de la devise (correspondant à FF3)',
    'column_currency-symbol'               => 'Symbole de la devise (correspondant à FF3)',
    'column_date-interest'                 => 'Date de calcul des intérêts',
    'column_date-book'                     => 'Date d\'enregistrement de la transaction',
    'column_date-process'                  => 'Date de traitement de la transaction',
    'column_date-transaction'              => 'Date',
    'column_date-due'                      => 'Date d\'échéance de la transaction',
    'column_date-payment'                  => 'Date de paiement de la transaction',
    'column_date-invoice'                  => 'Date de facturation de la transaction',
    'column_description'                   => 'Description',
    'column_opposing-iban'                 => 'Compte opposé (IBAN)',
    'column_opposing-bic'                  => 'Compte opposé (BIC)',
    'column_opposing-id'                   => 'Compte opposé (ID correspondant à FF3)',
    'column_external-id'                   => 'ID externe',
    'column_opposing-name'                 => 'Compte opposé (nom)',
    'column_rabo-debit-credit'             => 'Indicateur de débit/crédit spécifique à Rabobank',
    'column_ing-debit-credit'              => 'Indicateur de débit/crédit spécifique à ING',
    'column_sepa-ct-id'                    => 'Référence de bout en bout SEPA',
    'column_sepa-ct-op'                    => 'Référence SEPA du compte opposé',
    'column_sepa-db'                       => 'Référence Unique de Mandat SEPA',
    'column_sepa-cc'                       => 'Code de rapprochement SEPA',
    'column_sepa-ci'                       => 'Identifiant Créancier SEPA',
    'column_sepa-ep'                       => 'Objectif externe SEPA',
    'column_sepa-country'                  => 'Code de pays SEPA',
    'column_tags-comma'                    => 'Tags (séparés par des virgules)',
    'column_tags-space'                    => 'Tags (séparés par un espace)',
    'column_account-number'                => 'Compte d’actif (numéro de compte)',
    'column_opposing-number'               => 'Compte opposé (numéro de compte)',
    'column_note'                          => 'Note(s)',
    'column_internal-reference'            => 'Référence interne',

];
