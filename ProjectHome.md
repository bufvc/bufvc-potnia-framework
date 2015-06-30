## Introduction ##

The BUFVC Potnia Framework is a release of source code based on the developments undertaken for the [British Universities Film and Video Council (BUFVC) website](http://bufvc.ac.uk) which currently performs federated searches across 9 major databases storing some 13 million records. The work was carried out by Invocrown Ltd in association with [BUFVC](http://bufvc.ac.uk) and [Royal Holloway University of London](http://www.rhul.ac.uk/) and financed by a research grant from the [Arts and Humanities Research Council (AHRC)](http://www.ahrc.ac.uk/). The federated search aspect is one of the advantages of using this framework, multiple databases can be searched at once.

The architecture of the framework was created with academic data-sets in mind but may be applied to regular data-sets also. This release should enable the creation of a web based interface and search engine for large or small databases. It is currently optimised for use with MySQL storage but can be adapted to use other storage formats.

Result sets can be exported in variety of formats in addition to regular, paginated, web pages, these formats include:


  * Atom
  * JSON
  * Dublin Core
  * BibTeX
  * Text
  * iCal

The framework is a modular framework that ideally will use one module per database included, this release is currently set to single-module mode and comes with a demo module (named Hermes) which contains 1,000 records of BUFVC DVD data that can be searched, edited or added to as a good way of familiarising potential users or developers with the functionality within.

There are a series of standard controllers (e.g. search.php, record.php, edit.php) which enable any module to inherit a series of standard forms and views for the module's database by simply using a naming convention (e.g. item\_search.php, item\_record.php, item\_edit.php) for the relevant templates.

Each module can contain it's own data source layer that abstracts the storage using [PEAR MDB](http://pear.php.net/manual/en/package.database.mdb.php) classes. It is worth familiarising yourself with these before beginning work on adapting the framework for your project.

## Requirements for installation ##

The framework needs to be installed on to a web server that has PHP5 and PEAR modules MDB, Mail and DB\_Table installed, the database engine used in the demo is MySQL so MySQL5 is required in order to install the working demo. The framework may be extended for other database engines.

## Installation guide ##

```
hg clone https://code.google.com/p/bufvc-potnia-framework/
```
  1. Either clone the latest repository as above or download the [non-tracked archive](http://code.google.com/p/bufvc-potnia-framework/downloads/detail?name=bufvc-potnia-framework_v0.1.zip&can=2&q=) and deploy into a web serving directory ( you will need to configure your webserver to create a host for the new site)
  1. Make the new /var and /etc directories world writeable, the installation script will write a config.php file into /etc and logs and uploads will write into /var on an ongoing basis
  1. Point your browser at http://path_to_repository and you should be presented with an installation screen
  1. Complete the form with details such as your MySQL username and password and the installation will automatically complete
  1. Reset the permissions on /etc to something safe, it is no longer required to be writeable.
  1. You should now have a working installation of Potnia with a demo database of 1000 records (the demo uses DVD titles) to search and view.

## Usage ##

TODO: Some basic how-to guides on things like:

  * Setting up your own database
  * Facets
  * Extending the functionality

## Licence ##

The source software is released under the terms of the GNU General Public License, version 3 http://www.gnu.org/licenses/gpl-3.0.html. This licence, amongst other things,  permits users to extend and change the code for their own use or distribution for free. The GPL v3 licence is a strong copyleft licence and if you include code under this license in a larger program, the larger program must be under this license too. This licensing also means the software is issued without any warranty and some attributions must remain intact.

Where 3rd party libraries are included in the distribution they are done so with their licence information intact. All 3rd party libraries are distributed within the terms of their licences.