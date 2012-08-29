HoneyField samples input requests to test for security events.

# Description

HoneyField is an application security event visualization system.

This php library contains a "client" for that system which effectively publishes
events to the main processing system for analysis and visualization.

There are two ways to use the honeyfield php client.  The first is to 
reference the PHP objects directly - which is appropriate for general 
php based applications.  The other is to use a wordpress plugin which 
is packaged here in honeyfield.zip and is written to leverage the general
php client.  Both offer configuration options described in more detail
below.

If you are interested in a client in another technology, please 
contact @mkonda or @JemuraiSecurity.  Java and Ruby/Rails versions
are under development.

The files here, including honeyfield.php and honey.php were developed 
by Jemurai, LLC and are open source under the MIT License.

# Installation

This section covers installation of the php HoneyField client.  There
are different steps for the wordpress plugin and a vanilla php application.

## Wordpress Plugin

1. Upload `honeyfield.zip` to the `/wp-content/plugins/` directory and unzip it.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Register at honeyfield.io to get your API key.
1. Adjust settings page per preferences.  Inputing an API key is required.

## PHP

1. Unzip honeyfield.zip and include config.php and honey.php.
1. Use "test_data.php" as an example

# Configuration

To configure HoneyField, adjust the following settings (either in config.php 
if used without wordpress, or through wordpress settings in the admin screens):

* API Key - The API Key is a unique key to you.  It can be used for multiple
 applications.  It is required.
* Application Name - The name of the application the event is part of.  Required.
* Sample Rate - Sample N of 100 requests.  This is a way to reduce the number of 
 events sent to the HoneyField system.  Defaults to 10%.
* Blocked fields - A list of input parameters to redact when sending them to 
 HoneyField.
* HoneyField Host - The server to send the data to.  Defaults to honeyfield.io.
 Allows extension to run your own host or change to a dev/test setup.
* Debug Mode - When in debug mode, the HoneyField client will provide more 
 verbose output useful for debugging.

# Design and Extension Points

When an event fires, a main Honey class coordinates what happens to that event.
It uses filters to limit the number of events.  It uses triggers to flag events
for publishing.  It uses a connector to post json to the service.  Both filters
and triggers can be added to the design easily.

## Filters

HoneyField contains a concept of a "Filter" which basically prevents an event
from firing and being sent to the HoneyField server.  The sample rate is 
implemented through a simple filter.  

## Triggers

Triggers are used to flag events to ensure that they get sent to the server.
Currently, the in the default config, there is only one trigger which always
flags events for reporting.  An extension could remove this and add a trigger
that looks at the uri or other parts of an event and only sends certain types
of events.

## Communication

The communication is separated into a separate Connector class which handles
actually sending the Event to the server.

# Frequently Asked Questions

## Why would I use honeyfield?

To easily put in place a way to notice application security events in a
wordpress or PHP application.

## How do I use honeyfield?



# Changelog 

## 1.0 
* Initial release.
