🌐 SNIPBOX — Digital Business Card Generator

SNIPBOX is a modern, lightweight web application that allows users to create and share personalized digital business cards with a clean and animated interface.
The project focuses on simplicity, speed, and elegant design, using PHP (OOP) for backend processing and TailwindCSS for a futuristic frontend experience.

🚀 Features
🔹 Landing Page (Home)

Smooth animations and gradient backgrounds

TailwindCSS-powered UI

Clear call-to-action to start creating a card

Consistent branding and visual identity

🔹 Create Page (Form Builder)

Users enter their information:

Full name

Job title

Short bio

Email

Phone number

LinkedIn & GitHub links

Theme selection (Modern / Professional / Creative)

Live preview updates as the user types

Form validation (PHP + JS)

Data saved securely (JSON or DB-ready structure)

Success animation + auto-redirect to the generated card page

🔹 Digital Card Page (Public Profile)

Beautiful, theme-based digital card

Animated avatar using the user's initial

Gradient backgrounds + floating effects

Clickable contact buttons

Fully responsive & mobile-friendly

“Copy Link” button with success message

Designed to be shareable with anyone

🧩 Tech Stack
Layer	Technology
Frontend	TailwindCSS, Vanilla JavaScript, CSS Animations
Backend	PHP (OOP Architecture)
Storage	JSON File (easily swappable to PDO/MySQL)
Design System	Gradient themes + animated UI components
📁 Project Structure (Simplified)
project/
│── index.php              # Landing Page
│── create.php             # Card creation page + PHP form handling
│── card.php               # Public digital card page
│── classes/
│     └── CardHandler.php  # OOP backend logic (validation, saving, loading)
│── data/
│     └── cards.json       # Generated cards stored here
│── assets/
│     └── css / js / images
│── README.md

🔒 Security Notes

JSON file is protected from direct public access

Input sanitized and validated server-side

Each card gets a unique ID for public sharing

No sensitive data stored besides user-provided info

🛠 Installation

Clone the repository

git clone https://github.com/YourUsername/SNIPBOX.git


Upload the project to any PHP hosting

Works on cPanel, shared hosting, InfinityFree, or XAMPP

Make sure the /data/cards.json file is writable

chmod 755 data/
chmod 644 data/cards.json


Open the site in the browser

https://yourdomain.com

🎯 Vision

SNIPBOX was created to offer a fast, elegant, and modern way for professionals to share their identity without traditional business cards — simple, stylish, and accessible anywhere.

🧑‍💻 Author

Built with ❤️ by [Your Name]
Feel free to contribute, open issues, or request features!

⭐ Support

If you like the project, don’t forget to star the repository ⭐
Your support motivates future updates!
