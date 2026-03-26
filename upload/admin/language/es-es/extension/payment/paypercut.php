<?php
// Heading
$_['heading_title'] = 'Pasarela de Pago Paypercut';

// Text
$_['text_extension'] = 'Extensiones';
$_['text_success'] = 'Éxito: ¡Has modificado el módulo de pago Paypercut!';
$_['text_edit'] = 'Editar Paypercut';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'Clave API';
$_['entry_operating_account'] = 'ID de Cuenta Operativa';
$_['entry_statement_descriptor'] = 'Descriptor de Extracto';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Modo de Pago';
$_['entry_webhook_url'] = 'URL de Webhook';
$_['entry_order_status'] = 'Estado del Pedido';
$_['entry_status'] = 'Estado';
$_['entry_sort_order'] = 'Orden de Clasificación';
$_['entry_logging'] = 'Habilitar Registro';
$_['entry_payment_method_config'] = 'Configuración del Método de Pago';

// Help
$_['help_api_key'] = 'Ingrese su Clave API de Paypercut desde el panel de control';
$_['help_operating_account'] = 'Ingrese su ID de Cuenta Operativa (se encuentra en el Panel de Paypercut)';
$_['help_statement_descriptor'] = 'Texto que aparece en el extracto bancario del cliente (máximo 22 caracteres). Dejar vacío para usar el predeterminado.';
$_['help_google_pay'] = 'Habilitar Google Pay como opción de pago';
$_['help_apple_pay'] = 'Habilitar Apple Pay como opción de pago';
$_['help_checkout_mode'] = 'Elija entre alojado (redirigir a página de Paypercut) o integrado (pago en su sitio)';
$_['help_webhook_url'] = 'Copie esta URL y configúrela en su Panel de Paypercut en Desarrolladores > Webhooks';
$_['help_logging'] = 'Habilitar registro de solicitudes API, eventos webhook y errores. Desactivar en producción a menos que esté depurando. Los registros pueden contener datos sensibles.';
$_['help_payment_method_config'] = 'Seleccione una configuración de método de pago (perfil de pago) para controlar qué métodos de pago están disponibles para los clientes. Dejar vacío para usar el predeterminado.';

// Error
$_['error_permission'] = 'Advertencia: ¡No tienes permiso para modificar el módulo de pago Paypercut!';
$_['error_api_key'] = '¡Clave API Requerida!';
$_['error_statement_descriptor'] = '¡El descriptor de extracto debe tener 22 caracteres o menos!';
$_['error_unsupported_currency'] = 'Advertencia: La moneda de su tienda (%s) no es compatible con Paypercut. Monedas compatibles: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Modo de Prueba';
$_['text_mode_live'] = 'Modo en Vivo';
$_['text_mode_unknown'] = 'Modo Desconocido';
$_['text_enabled'] = 'Habilitado';
$_['text_disabled'] = 'Deshabilitado';
$_['text_hosted'] = 'Alojado (Redirigir)';
$_['text_embedded'] = 'Integrado (En el sitio)';
$_['text_statement_preview'] = 'Vista Previa';
$_['text_webhook_info'] = 'Configure esta URL de webhook en su <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Panel de Paypercut</a>';
$_['text_webhook_configured'] = 'El webhook está configurado y activo';
$_['text_webhook_not_configured'] = 'Webhook no configurado';
$_['text_webhook_create'] = 'Crear Webhook Automáticamente';
$_['text_webhook_delete'] = 'Eliminar Webhook';
$_['text_webhook_creating'] = 'Creando webhook...';
$_['text_webhook_deleting'] = 'Eliminando webhook...';
$_['text_wallet_settings'] = 'Configuración de Billetera';
$_['text_testing_connection'] = 'Probando conexión...';
$_['text_connection_success'] = '¡Conexión exitosa!';
$_['text_connection_failed'] = 'Conexión fallida';

// Button
$_['button_test_connection'] = 'Probar Conexión';
