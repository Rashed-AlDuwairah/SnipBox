# 📇 SNIPBOX — Digital Business Card Generator

**SNIPBOX** is a modern, lightweight web application that allows users to create and share personalized digital business cards with a clean and animated interface.  
The project focuses on simplicity, speed, and elegant design, using **PHP (OOP)** for backend processing and **TailwindCSS** for a futuristic frontend experience.

---

## 🚀 Features

### 🔹 Landing Page (Home)
* Smooth animations and gradient backgrounds.
* TailwindCSS-powered UI with clear call-to-action.
* Consistent branding and visual identity.

### 🔹 Create Page (Form Builder)
* **User Input:** Full name, Job title, Bio, Email, Phone, and Social links.
* **Theme Selection:** Modern, Professional, or Creative.
* **Live Preview:** Real-time visual updates as you type.
* **Backend:** Robust validation (PHP + JS) and secure data handling.
* **Flow:** Success animations and auto-redirect upon completion.

### 🔹 Digital Card Page (Public Profile)
* Beautiful, theme-based responsive digital cards.
* Animated avatars and floating UI effects.
* Clickable contact buttons and "Copy Link" functionality.
* Designed for seamless sharing across mobile and desktop.

---

## 🧩 Tech Stack

| Layer | Technology |
| :--- | :--- |
| **Frontend** | TailwindCSS, Vanilla JavaScript, CSS Animations |
| **Backend** | PHP (OOP Architecture) |
| **Storage** | JSON File (easily swappable to PDO/MySQL) |
| **Design System** | Gradient themes + animated UI components |

---

## 📁 Project Structure (Simplified)

```
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
```
🔒 Security Notes
JSON file is protected from direct public access.

Input sanitized and validated server-side.

Each card gets a unique ID for public sharing.

No sensitive data stored besides user-provided info.

🛠 Getting Started
Clone the repository:
git clone [https://github.com/Rashed-AlDuwairah/SNIPBOX.git](https://github.com/Rashed-AlDuwairah/SNIPBOX.git)

🎯 Vision
SNIPBOX was created to offer a fast, elegant, and modern way for professionals to share their identity without traditional business cards — simple, stylish, and accessible anywhere.

🧑‍💻 Author
Built with ❤️ by [Your Name] Feel free to contribute, open issues, or request features!
