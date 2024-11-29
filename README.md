# AzadWeek 📅  

**AzadWeek** is a lightweight Telegram bot designed to inform users whether the current week is **even** or **odd**. Built with PHP, it’s tailored for students, instructors, and anyone needing quick access to this information in a localized Persian calendar format.  

---

## 🔍 Key Features  

- **Week status checker**: Displays whether the current week is even or odd based on the Persian calendar.  
- **User data tracking**: Saves user states for improved interactions and analysis.  
- **Admin tools**: Manage the bot easily with pre-defined admin roles.  
- **Simple setup**: Deploy and integrate with Telegram in minutes.  

---

## 🛠 Prerequisites  

Before running the bot, ensure you have:  
1. **PHP 7.4 or later** installed on your server.  
2. A **Telegram Bot Token**, which you can obtain from [BotFather](https://core.telegram.org/bots#botfather).  
3. **jdf.php** library for Persian calendar management (already included in the source code).  
4. **Writable access** to the JSON files (`user_states.json` and `users.json`) for storing user data.  

---

## 🚀 Installation  

1. Clone the repository to your server:  
   ```bash
   git clone https://github.com/yourusername/AzadWeek.git
   ```  

2. Navigate to the project directory:  
   ```bash
   cd AzadWeek
   ```  

3. Configure the bot:  
   - Open `AzadWeek.php` and update the following variables:  
     - `$token`: Replace with your Telegram Bot Token.  
     - `$channel_username`: Add your channel username (without `@`).  
     - `$admin_chat_ids`: Add the Telegram IDs of bot admins.  

4. Set up the webhook:  
   Use the following URL format to link your bot to the server:  
   ```bash
   https://api.telegram.org/bot<YourToken>/setWebhook?url=<YourServerURL>/AzadWeek.php
   ```  

   Example:  
   ```bash
   https://api.telegram.org/bot123456:ABCDEF/setWebhook?url=https://example.com/AzadWeek.php
   ```  

---

## 📂 Project Structure  

```
AzadWeek/  
├── AzadWeek.php         # Main bot logic  
├── jdf.php              # Persian calendar library  
├── user_states.json     # Tracks user interaction states  
├── users.json           # Stores user information  
```  

---

## 📋 Usage  

1. **Start the bot**: After setting up the webhook, open the bot in Telegram and send `/start`.  
2. **Ask the bot**: Send messages like "این هفته زوجه؟" or "وضعیت هفته" to get the current week status.  
3. **Admin commands**: Admins can manage users and settings by editing the JSON files directly or extending the bot’s logic.  

---

## 🖥 Extending the Bot  

AzadWeek is designed with simplicity and extensibility in mind. Here are some ideas to expand its functionality:  
- **Add notifications**: Notify users about upcoming events or deadlines.  
- **Custom commands**: Implement additional features like semester reminders or personalized messages.  
- **Multi-language support**: Extend the bot to support other languages beyond Persian.  

To contribute, fork the repository, make your changes, and submit a Pull Request.  

---

## 🛠 Troubleshooting  

### Common Issues:  
- **Webhook not working**: Ensure the URL is publicly accessible and SSL-certified.  
- **No response from bot**: Verify that the `$token` is correctly set and matches your bot's token.  
- **Permission denied**: Ensure `user_states.json` and `users.json` have write permissions.  

---

## 🧑‍💻 Contributing  

We welcome contributions from developers! To get started:  
1. Fork the repository.  
2. Create a new branch for your feature:  
   ```bash
   git checkout -b feature-name
   ```  
3. Commit your changes and push them:  
   ```bash
   git push origin feature-name
   ```  
4. Open a Pull Request and describe your changes.  

---

## 📜 License  

This project is licensed under the [MIT License](LICENSE).  

---

## 👋 Contact  

- **Telegram Support**: [@ArianPashae](https://t.me/ArianPashae)  
- **Email**: info@arianpashae.com
- **WebSite**: [ArianPashae.com](https://arianpashae.com)

**AzadWeek: Simplifying your schedule, one week at a time.** 🚀  
