#CloudFrontAccess
Sample code that shows differences between accessing AWS CloudFront Service using official AWS PHP SDK and dedicated class
that was written based on offical CloudFront documentation.

##Project content
In repository you will find 3 files:

* composer.json - contains information about project dependencies
* src/sdk.php - contains sample code that shows how to access CloudFront service using official AWS PHP SDK. Before executing the code make sure you have installed the SDK (see below for details)
* src/CloudFrontClient.php - contains a sample class that enables to access CloudFront service without using the SDK

##Configuration
To connect to AWS CloudFront service you have to provide credentials:

* Access key ID,
* Secret key ID.

Both sdk.php and CloudFrontClient are expectig to find those values in eviroment variables called:

* ACCESS_KEY_ID
* SECRET_KEY_ID

so be sure to provide them before running the code.

**Notice**: If you provide the correct enviroment variables, but you still keep getting an error chances are, that the problem lays in your PHP configuration.
Be sure to set the `variables_order` setting to value `EGPCS`.

##Running sdk.php
Code in this file lists all available distributions.

##Running CloudFronClient.php
Code in this file lists all available distributions by default, but can also:

* list invalidations for given distribution,

* show details for given invalidation,

* add new invalidation for resources in given distributions.

To use those additional functions you have to uncomment apropriate lines at the end of the file and provide required arguments.

All responses from server are presented as raw XML.

##AWS PHP SDK installation
In order to run the code from sdk.php you have to install the official AWS PHP SDK in 3 simple steps:

1. Go to directory where composer.json file is placed.

1. Install Composer with command:
curl -sS https://getcomposer.org/installer | php

    **Note**: If you install Composer for the first time you might need to change your PHP configuration.
The installer will give you information about settings that need to be altered.

1. Install SDK with dependencies
php composer.phar install

That's all. After performing those operations you should end up with new directory "vendor" where resides the SDK.

##References
* [AWS PHP SDK](http://aws.amazon.com/sdkforphp/)
* [CloudFront API](http://docs.aws.amazon.com/aws-sdk-php-2/latest/namespace-Aws.CloudFront.html)
* [Composer](http://getcomposer.org/) - Dendency Manager for PHP