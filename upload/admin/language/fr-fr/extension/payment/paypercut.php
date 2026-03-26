<?php
// Heading
$_['heading_title'] = 'Passerelle de Paiement Paypercut';

// Text
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Succès : Vous avez modifié le module de paiement Paypercut !';
$_['text_edit'] = 'Modifier Paypercut';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'Clé API';
$_['entry_operating_account'] = 'ID du Compte Opérationnel';
$_['entry_statement_descriptor'] = 'Descripteur de Relevé';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Mode de Paiement';
$_['entry_webhook_url'] = 'URL du Webhook';
$_['entry_order_status'] = 'Statut de Commande';
$_['entry_status'] = 'Statut';
$_['entry_sort_order'] = 'Ordre de Tri';
$_['entry_logging'] = 'Activer la Journalisation';
$_['entry_payment_method_config'] = 'Configuration des Méthodes de Paiement';

// Help
$_['help_api_key'] = 'Entrez votre clé API Paypercut depuis le tableau de bord';
$_['help_operating_account'] = 'Entrez votre ID de compte opérationnel (disponible dans le tableau de bord Paypercut)';
$_['help_statement_descriptor'] = 'Texte qui apparaît sur le relevé bancaire du client (max 22 caractères). Laisser vide pour utiliser la valeur par défaut.';
$_['help_google_pay'] = 'Activer Google Pay comme option de paiement';
$_['help_apple_pay'] = 'Activer Apple Pay comme option de paiement';
$_['help_checkout_mode'] = 'Choisissez entre hébergé (redirection vers la page Paypercut) ou intégré (paiement sur votre site) pour l\'expérience de paiement';
$_['help_webhook_url'] = 'Copiez cette URL et configurez-la dans votre tableau de bord Paypercut sous Développeurs > Webhooks';
$_['help_logging'] = 'Activer la journalisation des requêtes API, événements webhook et erreurs. Désactiver en production sauf pour le débogage. Les journaux peuvent contenir des données sensibles.';
$_['help_payment_method_config'] = 'Sélectionnez une configuration de méthode de paiement (profil de paiement) pour contrôler quelles méthodes de paiement sont disponibles pour les clients. Laisser vide pour utiliser la valeur par défaut.';

// Error
$_['error_permission'] = 'Attention : Vous n\'avez pas la permission de modifier le module de paiement Paypercut !';
$_['error_api_key'] = 'Clé API requise !';
$_['error_statement_descriptor'] = 'Le descripteur de relevé doit contenir 22 caractères ou moins !';
$_['error_unsupported_currency'] = 'Attention : La devise de votre boutique (%s) n\'est pas prise en charge par Paypercut. Devises prises en charge : BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Mode Test';
$_['text_mode_live'] = 'Mode Production';
$_['text_mode_unknown'] = 'Mode Inconnu';
$_['text_enabled'] = 'Activé';
$_['text_disabled'] = 'Désactivé';
$_['text_hosted'] = 'Hébergé (Redirection)';
$_['text_embedded'] = 'Intégré (Sur site)';
$_['text_statement_preview'] = 'Aperçu';
$_['text_webhook_info'] = 'Configurez cette URL de webhook dans votre <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">tableau de bord Paypercut</a>';
$_['text_webhook_configured'] = 'Le webhook est configuré et actif';
$_['text_webhook_not_configured'] = 'Webhook non configuré';
$_['text_webhook_create'] = 'Créer un Webhook Automatiquement';
$_['text_webhook_delete'] = 'Supprimer le Webhook';
$_['text_webhook_creating'] = 'Création du webhook...';
$_['text_webhook_deleting'] = 'Suppression du webhook...';
$_['text_wallet_settings'] = 'Paramètres du Portefeuille';
$_['text_testing_connection'] = 'Test de la connexion...';
$_['text_connection_success'] = 'Connexion réussie !';
$_['text_connection_failed'] = 'Échec de la connexion';

// Button
$_['button_test_connection'] = 'Tester la Connexion';
