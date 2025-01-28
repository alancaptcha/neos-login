# Alan.NeosLogin

Neos plugin to integrate [AlanCaptcha](https://alancaptcha.com/) into the Backend Login. Note that it will only work with the default Login Form.

## Installation

Require the package using composer:

```bash
composer require alancaptcha/neos-login
```

## Configuration

Add the following configuration to your `Settings.yaml`:

```yaml
Alan:
    NeosLogin:
        active: true
        apiKey: 'API_KEY'
        siteKey: 'SITE_KEY'
        monitorTag: 'MONITOR_TAG (optional)'
        lang:
            unverifiedtext: 'Unverified'
            verifiedtext: 'Verified'
            retrytext: 'Retry'
            workingtext: 'Working...'
            starttext: 'Start'
```

`lang` settings are optional and can be used to customize the text of the captcha element.
