# HPM SGRecast

The beginnings of an integration with StreamGuys SGRecast API.

- Contributors: jwcounts
- Stable tag: v0.1
- License: GPLv2
- License URI: https://www.gnu.org/licenses/gpl-2.0.html

## Requirements

* PHP >= 8.0
* Composer - [Install](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)

## Installation

1. Import this repository and run `composer update` to install the one dependency

2. Create a `.env` file and add these settings:
  * `RECAST_USERNAME` = Email address associated with your SGRecast account
  * `RECAST_PASSWORD` = SGRecast account password
  * `RECAST_API_ROOT` = URL for your SGRecast instance
  * `RECAST_CLIENT_ID` = Client ID (provided by StreamGuys)
  * `RECAST_CLIENT_SECRET` = Client secret key (provided by StreamGuys)

## Usage

So far, I have only added the ability to retrieve lists of podcasts as well as individual podcast feeds from SGRecast.