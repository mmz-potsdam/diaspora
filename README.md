Front-end for diaspora.juedische-geschichte-online.net
=================================================

License
-------
    Code for the Front-end of diaspora.juedische-geschichte-online.net

    (C) 2023-2024 Moses Mendelssohn Center for European-Jewish Studies (MMZ)
        Daniel Burckhardt


    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    A public copy of the site must not give the impression of being
    operated by the MMZ.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

Third Party Code
----------------
This projects builds on numerous third-party projects under a variety of
Open Source Licenses. Please check `composer.json` for these dependencies.

The XSLT-Stylesheets are based on the files from
    https://github.com/haoess/dta-tools/tree/master/stylesheets

Installation
------------
### Requirements

- PHP >= 8.1 (check with `php -v`)
- composer (check with `composer -v`; if it is missing, see https://getcomposer.org/)
- `convert` (for image tiles, check with `which convert`; if it is missing, install e.g. with `sudo apt-get install imagemagick`)
- Java 1.8 (for XSLT and Solr, check with `java -version`; if it is missing, install e.g. with `sudo apt-get install openjdk-8-jdk`)
- bin/saxon9he.jar (Download from https://sourceforge.net/projects/saxon/files/Saxon-HE/9.9/SaxonHE9-9-1-8J.zip/download)

In a fitting directory (e.g. `/var/www`), clone the project

    git clone https://github.com/burki/diaspora.git

If you don't have `git` installed, you can also download the project as ZIP-file
and extract it manually.

Change into the newly created project-directory

    cd diaspora
    composer install

### Adjust Local Settings

- vi .env.local (not commited)

### Database

- bin/console doctrine:database:create
- bin/console doctrine:schema:create

### Directory Permissions for cache and logs

- sudo setfacl -R -m u:www-data:rwX ./var
- sudo setfacl -dR -m u:www-data:rwX ./var

- sudo setfacl -R -m u:www-data:rwX ./public/viewer
- sudo setfacl -dR -m u:www-data:rwX ./public/viewer

### SCSS compilation
In a `prod` environment, generate `public/css/base.css` and `public/css/print.css`

- ./bin/console scss:compile

Adding and updating Content
---------------------------
TEI files and page facsimiles are located in `data` in the
corresponding `tei` or `img/source-xxxxx` folders.

Follow the following commands to add the first source to the site:

Add the author (if set) to the `Person` table:

    ./bin/console article:author --insert-missing data/tei/source-00001.de.xml

Add the source

    ./bin/console article:header --insert-missing data/tei/source-00001.de.xml

Refresh the source (this will fetch every persName / orgName / placeName with GND or TGN identifier)

    ./bin/console article:refresh data/tei/source-00001.de.xml

If we have a source with page facsimile as hinted by

    <classCode scheme="http://juedische-geschichte-online.net/doku/#genre">Quelle:Text</classCode>

We can now generate the tiles

    ./bin/console source:tiles data/tei/source-00001.de.xml

(`convert` from the ImageMagick package is called to generate the tiles in `public/viewer/source-00001/`)

And generate the METS-container needed for `iview`

    ./bin/console source:mets data/tei/source-00001.de.xml > public/viewer/source-00001/source-00001.de.mets.xml

You can now preview the source at

    http://HOST/quelle/source-1

If you make changes, you can update all the metadata by running again

    ./bin/console article:refresh data/tei/source-00001.de.xml

If you are happy with the display, you can publish it:

    ./bin/console article:header --publish data/tei/source-00001.de.xml

It should now show up connected to the author.


Development Notes
-----------------
### Local Web Server

- symfony server:start
- Listening on https://127.0.0.1:8000

Tweaking the site
-----------------
### Translate messages and routes

    ./bin/console translation:extract de --dir=./src/ --dir=./templates/ --output-dir=./translations --enable-extractor=jms_i18n_routing
