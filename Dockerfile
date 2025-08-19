# استخدم نسخة PHP مع CLI
FROM php:8.2-cli

# ثبّت الامتدادات الضرورية
RUN docker-php-ext-install pdo pdo_mysql

# أنشئ مجلد العمل
WORKDIR /app

# انسخ المشروع
COPY . .

# اكشف المنفذ 10000 (Render بيستخدمه)
EXPOSE 10000

# شغّل السيرفر المدمج
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
