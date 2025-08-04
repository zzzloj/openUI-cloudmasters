import nodemailer from 'nodemailer';

// Конфигурация SMTP
const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST || 'localhost',
  port: parseInt(process.env.SMTP_PORT || '25'),
  secure: parseInt(process.env.SMTP_PORT || '25') === 465, // true для 465, false для других портов
  auth: process.env.SMTP_USER && process.env.SMTP_PASS ? {
    user: process.env.SMTP_USER,
    pass: process.env.SMTP_PASS,
  } : undefined,
});

// Функция для отправки email активации
export async function sendActivationEmail(email: string, username: string, activationCode: string) {
  const activationLink = `${process.env.NEXT_PUBLIC_BASE_URL || 'http://89.111.170.207'}/auth/activate?code=${activationCode}&email=${encodeURIComponent(email)}`;
  
  const mailOptions = {
    from: `"CloudMasters" <${process.env.SMTP_USER || 'admin@cloudmasters.ru'}>`,
    to: email,
    subject: 'Активация аккаунта - CloudMasters',
    html: `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>Активация аккаунта</title>
        <style>
          body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
          .container { max-width: 600px; margin: 0 auto; padding: 20px; }
          .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
          .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
          .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
          .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
      </head>
      <body>
        <div class="container">
          <div class="header">
            <h1>🎉 Добро пожаловать в CloudMasters!</h1>
          </div>
          <div class="content">
            <h2>Здравствуйте, ${username}!</h2>
            <p>Спасибо за регистрацию на портале CloudMasters. Для завершения регистрации необходимо активировать ваш аккаунт.</p>
            
            <p><strong>Код активации:</strong> <span style="font-size: 18px; font-weight: bold; color: #667eea;">${activationCode}</span></p>
            
            <p>Или нажмите на кнопку ниже для автоматической активации:</p>
            
            <div style="text-align: center;">
              <a href="${activationLink}" class="button">Активировать аккаунт</a>
            </div>
            
            <p>Если кнопка не работает, скопируйте и вставьте эту ссылку в браузер:</p>
            <p style="word-break: break-all; background: #f0f0f0; padding: 10px; border-radius: 5px; font-size: 12px;">${activationLink}</p>
            
            <p><strong>Внимание:</strong> Код активации действителен в течение 24 часов.</p>
          </div>
          <div class="footer">
            <p>Это письмо отправлено автоматически. Пожалуйста, не отвечайте на него.</p>
            <p>© 2024 CloudMasters. Все права защищены.</p>
          </div>
        </div>
      </body>
      </html>
    `,
  };

  try {
    await transporter.sendMail(mailOptions);
    console.log(`Email активации отправлен на ${email}`);
    return true;
  } catch (error) {
    console.error('Ошибка отправки email активации:', error);
    return false;
  }
}

// Функция для отправки email восстановления пароля
export async function sendPasswordResetEmail(email: string, username: string, resetCode: string) {
  const resetLink = `${process.env.NEXT_PUBLIC_BASE_URL || 'http://89.111.170.207'}/auth/reset-password?code=${resetCode}&email=${encodeURIComponent(email)}`;
  
  const mailOptions = {
    from: `"CloudMasters" <${process.env.SMTP_USER || 'admin@cloudmasters.ru'}>`,
    to: email,
    subject: 'Восстановление пароля - CloudMasters',
    html: `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>Восстановление пароля</title>
        <style>
          body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
          .container { max-width: 600px; margin: 0 auto; padding: 20px; }
          .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
          .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
          .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
          .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
          .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
      </head>
      <body>
        <div class="container">
          <div class="header">
            <h1>🔐 Восстановление пароля</h1>
          </div>
          <div class="content">
            <h2>Здравствуйте, ${username}!</h2>
            <p>Мы получили запрос на восстановление пароля для вашего аккаунта на портале CloudMasters.</p>
            
            <p><strong>Код восстановления:</strong> <span style="font-size: 18px; font-weight: bold; color: #667eea;">${resetCode}</span></p>
            
            <p>Или нажмите на кнопку ниже для перехода к восстановлению пароля:</p>
            
            <div style="text-align: center;">
              <a href="${resetLink}" class="button">Восстановить пароль</a>
            </div>
            
            <p>Если кнопка не работает, скопируйте и вставьте эту ссылку в браузер:</p>
            <p style="word-break: break-all; background: #f0f0f0; padding: 10px; border-radius: 5px; font-size: 12px;">${resetLink}</p>
            
            <div class="warning">
              <p><strong>⚠️ Внимание:</strong></p>
              <ul>
                <li>Код восстановления действителен в течение 1 часа</li>
                <li>Если вы не запрашивали восстановление пароля, проигнорируйте это письмо</li>
                <li>Никогда не передавайте код восстановления третьим лицам</li>
              </ul>
            </div>
          </div>
          <div class="footer">
            <p>Это письмо отправлено автоматически. Пожалуйста, не отвечайте на него.</p>
            <p>© 2024 CloudMasters. Все права защищены.</p>
          </div>
        </div>
      </body>
      </html>
    `,
  };

  try {
    await transporter.sendMail(mailOptions);
    console.log(`Email восстановления пароля отправлен на ${email}`);
    return true;
  } catch (error) {
    console.error('Ошибка отправки email восстановления пароля:', error);
    return false;
  }
}

// Функция для генерации случайного кода
export function generateCode(length: number = 6): string {
  const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  let result = '';
  for (let i = 0; i < length; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
} 