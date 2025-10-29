#Thunderbird Theme

Based on the Renovation theme for Webspark 2: Implements Web Standards 2.0

## Installation

Ensure that the Radix & Renovation themes and the components moule are installed.

Renovation theme uses [Webpack](https://webpack.js.org) to compile and bundle SASS and JS.

#### Step 1
Go to the root of Thunderbird theme and run the following command: `fin run npm install`.

#### Step 2
Update `proxy` in **webpack.mix.js**.

#### Step 3
Run the following command to compile Sass and watch for changes: `fin run npm run watch`.
You can also use the shorthand of this command: `fin npm watch`

#### Step 4
Run `npm run production` to compile your css + js files. If you get an error, run
`export NODE_OPTIONS=--openssl-legacy-provider` then rerun the production command.


Currently the compiled css is committed to the repo. Once we have pipelines running
we can gitignore this, however be aware for now that conflicts will arise when merging two
branches that have both made style changes.

To resolve this, you will need to merge the SCSS changes, then re-compile CSS.