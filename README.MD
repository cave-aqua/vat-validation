# VAT Validation Service

This project is a Symfony-based application that provides VAT validation and address formatting services for various European countries.

## Installation

1. Clone the repository:
    ```sh
    git clone https://github.com/cave-aqua/vat-validation
    ```
2. Navigate to the project directory:
    ```sh
    cd vat-validation
    ```
3. Install the dependencies(only on production):
    ```sh
    composer install
    ```

## Running Locally with DDEV

1. Install DDEV by following the instructions on the [DDEV documentation](https://ddev.readthedocs.io/en/stable/#installation).
2. Start the DDEV environment:
    ```sh
    ddev start
    ```
3. Start the DDEV environment:
    ```sh
    ddev composer install
    ```
4. Access the project at `https://vat-layer.ddev.site`.