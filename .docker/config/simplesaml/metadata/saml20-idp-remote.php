<?php

$idpBaseURL = getenv('SIMPLESAMLPHP_IDP_BASE_URL');
$idpEntityId = getenv('SIMPLESAMLPHP_IDP_ENTITYID') ?: $idpBaseURL;
$fallbackBinding = getenv('SIMPLESAMLPHP_IDP_DEFAULT_BINDING');

$bindingKeys = [
    'SIMPLESAMLPHP_IDP_HTTP_REDIRECT_BINDING',
    'SIMPLESAMLPHP_IDP_HTTP_POST_BINDING',
    'SIMPLESAMLPHP_IDP_SOAP_BINDING',
    'SIMPLESAMLPHP_IDP_HTTP_ARTIFACT',
    'SIMPLESAMLPHP_IDP_LOGOUT_HTTP_REDIRECT_BINDING',
    'SIMPLESAMLPHP_IDP_LOGOUT_HTTP_POST_BINDING',
    'SIMPLESAMLPHP_IDP_LOGOUT_SOAP_BINDING',
    'SIMPLESAMLPHP_IDP_LOGOUT_HTTP_ARTIFACT',
];

// Initialise bindings.
$bindings = [];

// Set bindings based on their associated env variables.
foreach ($bindingKeys as $key) {
    $envVar = getenv($key);

    // Special for LOGOUT: fallback to non-logout sibling if present.
    if (str_contains($key, 'LOGOUT') && empty($envVar)) {
        $nonLogoutKey = str_replace('LOGOUT_', '', $key);
        $envVar = getenv($nonLogoutKey);
    }

    // Special for HTTP-Redirect: still attempt a fallback if empty.
    // This is because SimpleSAMLphp uses HTTP-Redirect binding by default.
    if ($key === 'SIMPLESAMLPHP_IDP_HTTP_REDIRECT_BINDING' && empty($envVar)) {
        $envVar = $fallbackBinding;
    }

    if (empty($envVar)) {
        continue;
    }

    $bindings[$key] = str_starts_with($envVar, 'http') ? $envVar : $idpBaseURL . $envVar;
}

$metadata[$idpEntityId] = [
  'entityid' => $idpEntityId,
  'contacts' => [],
  'metadata-set' => 'saml20-idp-remote',
  'sign.authnrequest' => filter_var(getenv('SIMPLESAMLPHP_IDP_SIGN_AUTH'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
  'SingleSignOnService' => array_values(array_filter([
    !empty($bindings['SIMPLESAMLPHP_IDP_HTTP_REDIRECT_BINDING']) ? [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_HTTP_REDIRECT_BINDING'],
    ] : null,
    !empty($bindings['SIMPLESAMLPHP_IDP_HTTP_POST_BINDING']) ? [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_HTTP_POST_BINDING'],
    ] : null,
    !empty($bindings['SIMPLESAMLPHP_IDP_SOAP_BINDING']) ? [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_SOAP_BINDING'],
    ] : null,
    !empty($bindings['SIMPLESAMLPHP_IDP_HTTP_ARTIFACT']) ? [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_HTTP_ARTIFACT'],
    ] : null,
  ])),
  'SingleLogoutService' => array_values(array_filter([
    !empty($bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_REDIRECT_BINDING']) ? [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_REDIRECT_BINDING'],
    ] : null,
    !empty($bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_POST_BINDING']) ? [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_POST_BINDING'],
    ] : null,
    !empty($bindings['SIMPLESAMLPHP_IDP_LOGOUT_SOAP_BINDING']) ? [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_LOGOUT_SOAP_BINDING'],
    ] : null,
    !empty($bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_ARTIFACT']) ? [
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
      'Location' => $bindings['SIMPLESAMLPHP_IDP_LOGOUT_HTTP_ARTIFACT'],
    ] : null,
  ])),
  'ArtifactResolutionService' => !empty($bindings['SIMPLESAMLPHP_IDP_SOAP_BINDING']) ? [[
    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
    'Location' => $bindings['SIMPLESAMLPHP_IDP_SOAP_BINDING'],
    'index' => 0,
  ]] : [],
  'NameIDFormats' => [
    'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
    'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
    'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
    'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
  ],
  'signature.algorithm' => getenv('SIMPLESAMLPHP_IDP_SIGNATURE_ALGORITHM') ?: 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
  'keys' => [
    [
      'encryption' => filter_var(getenv('SIMPLESAMLPHP_IDP_CERT_ENCRYPT'), FILTER_VALIDATE_BOOLEAN),
      'signing' => filter_var(getenv('SIMPLESAMLPHP_IDP_CERT_SIGNING'), FILTER_VALIDATE_BOOLEAN),
      'type' => getenv('SIMPLESAMLPHP_IDP_CERT_TYPE') ?: 'X509Certificate',
      'X509Certificate' => getenv('SIMPLESAMLPHP_IDP_CERT'),
    ],
  ],
];
