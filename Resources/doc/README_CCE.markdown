# CCETC/FOSUserBundle - README

This bundle is a forked version of the [FOSUserBundle](https://github.com/FriendsOfSymfony/FOSUserBundle).
It contains some minor customizations to the FOS bundle.
This bundle is used in all CCETC Symfony web applications.

## List of customizations
Our customizations are listed below.  Some are documented in more detail below under "Custom Features" or "Installation/Configuration".

- improved layout and design using Twitter's bootstrap
- added a "approval" option to registration that only lets users log in one an admin has approved them
- hid all uses of username and default to e-mail address as the username
- files with specific user entity fields (profile, register, etc) are now included with .dist versions to allow for easy customization
- minor lanuage and interface customizations
- a template can be configured to be included with the registration form, which is useful for displaying a friendly message to explain why users may want to create an account


## Dependencies
The templates use [Twitter's Bootstrap](http://twitter.github.com/bootstrap/) css library (the js libraries are not used).  FOSUserBundle's templates must extend an external template (see below) so the template they extend should include bootstrap.

## Installation
### dist files
There are five files that are used to display the profile and registrations pages that refer to specific fields in the user entity.  As different user entities in different Symfony projects will have different fields, these files are only included as .dist files.  The files are:

	Form/Type/ProfileFormType.php
	Form/Type/RegistrationFormType.php
	Resources/translations/FOSUserBundle.en.yml
	Resources/views/Profile/edit_content.html.twig
	Resources/views/Profile/show_content.html.twig
	Resources/views/Registration/register_content.html.twig

For each you can simply copy the file and run as is, or copy and add your custom fields.

	cp Form/Type/ProfileFormType.php.dist Form/Type/ProfileFormType.php

### configuration
We have added some settings to the configuration.  *NOTE:* the bundle requires that you defined both an ``application_title`` and a ``base_layout``.

- ``application_title`` - name of application (required)
- ``admin_email`` - email address to send new account notices to if account approval is enabled (default: empty)
- ``base_layout`` - the twig template that FOSUserBundle templates should extend (required)
- ``use_page_header`` (boolean) - whether or not templates should put headings in the SonataAdmin page_header block (default: false)
- ``flash_name - the name of the flash to use for user messages (default: fos_user_success)
- ``why_register_template`` - a template to include with the register template (default: empty)

Example settings configuration for a site that uses SonataAdmin:

	fos_user:
	  settings:
		application_title: My Application
		admin_email: myemail@gmail.com
		base_layout: SonataAdminBundle::standard_layout.html.twig
		use_page_header: true
		flash_name: sonata_flash_success
		why_register_template: ::_whyRegister.html.twig

### routes
The bundle assume that you have a ``home`` route, and uses this to redirect from login/register pages when logged in.

## Documentation
All ISSUES, IDEAS, and FEATURES are documented on the [trello board](https://trello.com/board/fosuserbundle/4f8f262a067c6a6d6001392e).

## Custom Features
### Account Approval
We have added an "approval" option to registration that only lets users log in once an admin has approved them. To enable Account Approval, use the configuration below.

	fos_user:
	  registration:
		approval:
		  enabled:    true
		  template:   FOSUserBundle:Registration:email.txt.twig

When a user creates an account, they will be told that they will be able to login once an Admin approves their account.  The email address specified in ``fos_user.settings.admin_email`` will recieve an e-mail notifying them of the new account.

## Areas for improvement / "broken windows"
### Group Management
The group management features of the original bundle have not been touched but are likely not functional with our fork of this bundle.  This bundle is meant to be used with our fork of SonataUserBundle, which handles group management.

### Base Layout
This bundle should not require that the templates extend an external template.  The bundle should come with a basic base template to be used by default.