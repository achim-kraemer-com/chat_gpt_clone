
# ChatGPT Clone

This is an open-source ChatGPT clone built with Symfony. It uses the ChatGPT API to answer your questions and start prompts.

## Features

*   Chat with an AI assistant powered by the ChatGPT API.
*   Start new conversations and view your chat history.
*   User registration and login.
*   Send emails with Mailgun.
*   Webpack Encore for asset management.

## Tech Stack

*   **Backend:** Symfony 6.2, PHP 8.1, Doctrine ORM, MySQL
*   **Frontend:** Twig, Webpack Encore, Babel
*   **API:** OpenAI API
*   **Docker:** Apache, MariaDB, Mailcatcher, Adminer

## Installation

1.  **Clone the repository:**

    ```bash
    git clone https://github.com/achim-kraemer-com/chat_gpt_clone.git
    cd sym-chat
    ```

2.  **Build and run the Docker containers:**

    ```bash
    docker-compose up -d
    ```

3.  **Install PHP dependencies:**

    ```bash
    docker-compose exec apache composer install
    ```

4.  **Install JavaScript dependencies:**

    ```bash
    docker-compose exec apache npm install
    ```

5.  **Build frontend assets:**

    ```bash
    docker-compose exec apache npm run build
    ```

6.  **Access the application:**

    Open your browser and navigate to `http://localhost`

## Usage

Once the application is running, you can create an account and start a new chat. The application will use the ChatGPT API to generate responses. You will need to add your OpenAI API key to the `.env` file in the `application` directory.

## License

This project is licensed under the MIT License. See the [LICENSE.md](LICENSE.md) file for details.
