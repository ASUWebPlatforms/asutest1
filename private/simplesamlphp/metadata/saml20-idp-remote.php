<?php

$metadata['http://federation.asu.edu/adfs/services/trust'] = [
    'entityid' => 'http://federation.asu.edu/adfs/services/trust',
    'contacts' => [
        [
            'contactType' => 'support',
        ],
    ],
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://federation.asu.edu/adfs/ls/',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://federation.asu.edu/adfs/ls/',
        ],
    ],
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://federation.asu.edu/adfs/ls/',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://federation.asu.edu/adfs/ls/',
        ],
    ],
    'ArtifactResolutionService' => [],
    'NameIDFormats' => [
        'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
        'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
        'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
    ],
    'keys' => [
        [
            'encryption' => true,
            'signing' => false,
            'type' => 'X509Certificate',
            'X509Certificate' => 'MIIC5jCCAc6gAwIBAgIQf1V5a4W5dplB/Lu/Xn5bEjANBgkqhkiG9w0BAQsFADAvMS0wKwYDVQQDEyRBREZTIEVuY3J5cHRpb24gLSBmZWRlcmF0aW9uLmFzdS5lZHUwHhcNMjUwOTA2MDExODIzWhcNMjgwOTA1MDExODIzWjAvMS0wKwYDVQQDEyRBREZTIEVuY3J5cHRpb24gLSBmZWRlcmF0aW9uLmFzdS5lZHUwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCvQGAa2JDJpcTaAXyTLyw5xL6gWLe6DDxFlPUUktGclKApUUH9O3cwHxMCTdUr/lhT8sNPp6Hv4Nm4hhLXLRL9bPtQjd+0h2w12CaEZq3eplU+McDN1SV+G9fMA4QCOV8OQIIX0m+OsU7WGC3hBOi5xfThtISdM1Rk+xiDMCWWIglJKDDAXsd26WqSDRgcf1fTLczdsSTrmdVwq3mFS484cy4u5GQ5DWnKChVd0gp51A6m2VyrMOubAPQntK1XXWZ74gTqPhxQP3NnXjwWSI2XY8tpB2pFU3OLyTxIO4NtE3VtCPWd/aRSLStBn3C1frOilj6wrdY5YOUx60SAzz6BAgMBAAEwDQYJKoZIhvcNAQELBQADggEBACrUwiDtQpirHBMID1ELUqlTYq6ggOL2RZNdePsxsjly/JrZaroF4lUERY9tROoVXSLKTPTEQdTEjRNQT8p1l3og7x94GqzMBXJMnkfI4uLCecv4GfjuGwauJRHc0oa3ZyUOngjRZPgtk+qEcIb+AGBurCWhGi0DbFcwAkG74EVTGTzGnSesYQbSEuFXPKbYzWkM9l13Hro9rPf8aXSH58O1dURpThmvdHGSNcnbUg85YTyXudLBSHddHcSH2WaNKN0bZcRZpZ4AmKSv4Yy+a5jCQ4G0IB1RNPpF3QkhZozfpWT2A/1C1BZdI73UnTRcOPqLmKc6VzlarN5lkYnfcbI=',
        ],
        [
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => 'MIIC4DCCAcigAwIBAgIQLRL2P3kSSL5HK32rrDIRTDANBgkqhkiG9w0BAQsFADAsMSowKAYDVQQDEyFBREZTIFNpZ25pbmcgLSBmZWRlcmF0aW9uLmFzdS5lZHUwHhcNMjIwOTI2MjAxNDU5WhcNMjUwOTI1MjAxNDU5WjAsMSowKAYDVQQDEyFBREZTIFNpZ25pbmcgLSBmZWRlcmF0aW9uLmFzdS5lZHUwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCbtlaCyM+aLuqaD3jdsJJE7m01QqZzucgFirYW5ygfHuOYzSuF0p025a8p6LKKlrjKQMwhxEgchp333f7NEMj0MPWjG/bsDTlS6UEdib9RcJpdK3WfKU9UoKY0huZzKqC9QqFkrCBjw6Te4QOvmmL/L32A/YKcNm0w++ABkZYI61sth8GXe/oqUFIk+84XcZq7hqRUhYcmx1cTQgSkO4hZR5Bm9r6MqvHjSCSX1EwX2vinZDXAVrBYzdO8RyWlNUmeZNwOa6xUvhXTBwYP0DzbVd9BoMetl4bQB08NhDkI4HVElr54UoTdkPqjxKM+wqdpZx0OMIdSviTxWm+Rc795AgMBAAEwDQYJKoZIhvcNAQELBQADggEBABW0VjbUyeTFqBzpY2uwKtSK8JZqpU52e5JD4J/ndyf2qxPAR+ylOY5HRPXkJVwCwOIPoNE0jdFhMRCa5BPxCslTC261GlH8+3MY2rIskYEKAYW3lnr6tjP/0iftbCaxE/nfMjjw1f4dm1o+vLr3GutPx2jZlDk+4ZKc3RqgQkpX7AN57rWZueLqzZzyA/AEgYSZTyUwWQJy1PTAkX5fyCMrEq4VKZSDRLSWvGjTH1LnHwetjLRGzWDI+Dfn6tl9VFTnzyaqb5DhnF9u+GRd41I7tpnX79p2Agw8diAsnfOLTPeHhLih684rJFiT67k5vTnL1qDKBhOBIJmctcQmA4w=',
        ],
        [
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => 'MIIC4DCCAcigAwIBAgIQG8bxmyQdg55HG/k1wNBhrTANBgkqhkiG9w0BAQsFADAsMSowKAYDVQQDEyFBREZTIFNpZ25pbmcgLSBmZWRlcmF0aW9uLmFzdS5lZHUwHhcNMjUwOTA2MDExODI1WhcNMjgwOTA1MDExODI1WjAsMSowKAYDVQQDEyFBREZTIFNpZ25pbmcgLSBmZWRlcmF0aW9uLmFzdS5lZHUwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCwYIXhUyK4LlsBWGra0Z4CU0fRIgQPL9DPLc5UhxyvimeMWkADTxLmbuHwI8d+pew1GpQk+Q4z5pSlRA/gWn19ijHSFE5eHdU0LF3TEgEFXid4cgUCyyKCOgnAmP/gij8YN25hSw4xoCEB923HK3fF8CANPduk2JOvd/6eqWR/NLBOWEbdsU2MRg6K0uF5pYWcLXk1PBJcGYWIO5zrMKlnZeoo6xVDN7grl5CAoDopjHyYms56qxI+TEhqFB713tRF/qkatjJT5gB8PfYkXxresfMtkUDSaEpyQCwSh4RBOuPpAxRoEq5FN+7zC0zFNL1tOQXB7ySPWLf3pCVEjTl1AgMBAAEwDQYJKoZIhvcNAQELBQADggEBAFOMPvF3wSj6ENjh+VmQjR9VU661cs/jclxvDORvO+xhSPUoLxNJojT/txFcIAqkb8nRWU1vpCiClSaaJBXIR6bjHi0NqcnwTBmJ6DnEAWhaeelYdwUGbKbCp+c9NG+CP4uR1AE49ppwIOy9No+sRCWrdDiSCO6OFHmXOPZdDpZz9HPksxAEDI8BYrUvXUbT0ecDzHiPlBNwbHe3daZ5XQ5ozKZxQO9UwgLQQL5IvET+eeswWcbd+Jd/l4aICykaoLdE9c7ou+FKtCsg57FVOM1f5Vqwn9ADn4sDF0yjwiuuI2aOsHTdBwDuW+1XpFWpWs9klu5VYMofb3IXxFbhyS4=',
        ],
    ],
];
