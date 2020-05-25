# AOPDF

This package is for those who want to create their own customized pdf service.
If you want to use the standard container service, use ao-pdf-docker: https://github.com/alex-oliveira/ao-pdf-docker



## Prerequisites

### Install "pdftk"
````
$ sudo snap install pdftk
````
https://linuxhint.com/install_pdftk_ubuntu/
https://askubuntu.com/questions/1028522/how-can-i-install-pdftk-in-ubuntu-18-04-and-later



## Installation

### 1) Install
````
$ composer require alex-oliveira/ao-pdf
````

### 2) Configure "config/app.php" file
````
'providers' => [
    /*
     * Package Service Providers...
     */
    AOPDF\AOPDFServiceProvider::class,
],
````

## Utilization

### Basic
````
````

### Using formatteres
````
````

### Available formatteres
````
````
