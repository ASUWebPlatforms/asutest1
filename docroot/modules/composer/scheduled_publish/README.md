# Scheduled Publish

This module introduces a field type for nodes to update the moderation state of
some content types.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/scheduled_publish).

To submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/scheduled_publish).


CONTENTS OF THIS FILE
---------------------

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. Navigate to Administration > Extend and enable the module.
2. Navigate to Administration > Configuration > Workflows > Workflow and
   enable a workflow for the content type.
3. Navigate to Administration > Structure > Content types >
   [Content type to edit] and add a field of the type "Scheduled publish" to
   the node bundle.
4. There will now be a "Scheduled Moderation" field set.
5. Do not set the Scheduled Publish field as a **required** field since 
   it's value will be set to *null*, after cron has made the transition.

Notice: You should run the drupal cron every few minutes to make sure that
updates of the moderation state are finished at the correct time.


## Maintainers

- Sascha Hannes - [SaschaHannes](https://www.drupal.org/u/saschahannes)
- Peter Majmesku - [peter-majmesku](https://www.drupal.org/u/peter-majmesku)
- Sergei Semipiadniy - [sergei_semipiadniy](https://www.drupal.org/u/sergei_semipiadniy)
- James Shields - [lostcarpark](https://www.drupal.org/u/lostcarpark)

Supporting organizations:

- publicplan GmbH - [publicplan-gmbh](https://www.drupal.org/publicplan-gmbh)
- schfug UG - [haftungsbeschränkt](https://www.drupal.org/schfug-ug)
