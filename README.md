# Bulk Import Project

## Setup Instructions

1. **Clone the repository**  
   ```bash
   git clone git@github.com:pranitdalavi/bulk-import.git

2. Configure environment file

You already have a .env file. You can edit it if needed.

Note: The .env file is currently not ignored in .gitignore.

3. Install dependencies : composer install

4. Run database migrations : php artisan migrate

5. Start the development server : php artisan serve

6. php artisan queue:work

7. Open your browser and visit:  http://127.0.0.1:8000

8. In terminal to run the tests : composer test