SpectralMandrillBundle
=================

Send transactional mail via Mandrill. This bundle is currently a Mandrill wrapper for Symfony 3.

The classes are all original classes provided by Mandrill and have been rewritten to work in the Symfony framework.

For the original API documents, please see: https://mandrillapp.com/api/docs

Prerequisites
-------------

In order to use this bundle, you will need to have an account with Mandrill which is currently a service through MailChimp.

http://mandrill.com

http://mandrill.com/pricing/

Installation
-----------

Add the bundle to your composer.json

```json
# composer.json
{
 "require": {
     "spectral/mandrill-bundle": "dev-master",
 }
}
```

Run composer install

```sh
php ./composer.phar install
```

Enable the bundle in the kernel

    <?php
    // app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Spectral\MandrillBundle\SpectralMandrillBundle(),
        );
    }