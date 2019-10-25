# COBAI : COntent Backup And Import
![magento](https://img.shields.io/badge/Made_by-Emakina.FR-black.svg?cacheSeconds=2592000)
![open source](https://img.shields.io/badge/Open_Source-♥-informational.svg?cacheSeconds=2592000)
![emakina.fr](https://img.shields.io/badge/Magento-2.3.x-important.svg?cacheSeconds=2592000)


> COBAI is a Magento2 module to transfer your CMS blocks, pages, images, and hierarchies from a platform to another platform.


## Compatibility
This plugin works with Magento **2.3.x**.

## What is COBAI for Magento2 ? 
When you run the export command line, COBAI creates a package with all your CMS data in your `var/export` directory.

After that, you just need to transfer your archive to the other platform in the `var/import` directory. Then, run the import command line.

❗️️ Don't forget to clean your `var/export` and `var/import` directories. Indeed, the import does not delete the imported files so that you can reuse it if you need it.

## Getting started
### Install
To install the module use composer
```
$ composer require  emakinafr/cobai
```

### Usage
To export an archive with pages, blocks, images and hierarchies, you need to run
```
$ bin/magento cobai:cms:export
```

To import an archive, you need to run
```
$ bin/magento cobai:cms:import <filename>
```

To clean your working directory, you need to run
```
$ rm -rf var/export var/import
```

## Advanced features

### Export Options

```
$ bin/magento cobai:cms:export --type [typeOption]
```

| Type Option 	        | Description                  	   | 
|-------------------    |------------------------------	   |
| archive       	    | export archive to zip file   	   |
| block          	    | export blocks to csv file        | 
| image         	    | export images to zip file    	   |  
| hierarchy     	    | export hierarchies to csv file   | 
| page          	    | export pages to csv file     	   | 

If you want to customize the name of your file you can use `--file=[name]`. You need to specify the path of this file, for example `var/export/archive.zip`


### Import Options
```
$ bin/magento cobai:cms:import --type [typeOption] <filename>
```

| Type Option 	        | Description                  	   | 
|-------------------    |------------------------------	   |
| archive       	    | import archive to zip file   	   |
| block          	    | import blocks to csv file        | 
| image         	    | import images to zip file    	   |  
| hierarchy    	        | import hierarchies to csv file   | 
| page          	    | import pages to csv file     	   | 

❗️️ By default, the option only adds changes except for the **hierarchy and image option** which systematically **replaces all data**.  

For blocks/pages, you can use `--force`, to update old blocks/pages.
