
# Getting started

## Installation

Please check the official laravel installation guide for server requirements before you start. [Official Documentation](https://laravel.com/docs/5.4/installation#installation)


Clone the repository

    git clone https://github.com/tanvir-ahmed-pavel/centralized_vehicle_rental_administration_system_be.git

Switch to the repo folder

    cd laravel-realworld-example-app

Install all the dependencies using composer

    composer install

Copy the example env file and make the required configuration changes in the .env file

    cp .env.example .env

Generate a new application key

    php artisan key:generate


Run the database migrations (**Set the database connection in .env before migrating**)

    php artisan migrate
    
Install Passport

    php artisan passport:intall

Start the local development server

    php artisan serve
    
Or start the Network development server

    composer serve

You can now access the server at http://localhost:8000
