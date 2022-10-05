# Preview by Site
Proof of Concept 'preview by site' functionality, to allow template previewing across a network.

![Preview a Template](./assets/preview.gif)

Assuming we have a central 'control' network site responsible for building FSE theme templates, we can preview those themes on individual sub-sites without copying the template across to those sites.

This would likely be used in conjunction with a 'template sync' plugin, that will allow you to sync your templates to your sub sites after you have previewed them.


## Build 
Built using the [WordPress Create Block Script](https://www.npmjs.com/package/@wordpress/create-block).

To get up and running, pull this block into your WordPress plugins directory, `cd` into the block and run the following commands:

`npm install`
`npm run build`
