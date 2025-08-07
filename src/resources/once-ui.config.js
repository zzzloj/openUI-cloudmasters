import { home } from "./content";

// IMPORTANT: Replace with your own domain address - it's used for SEO in meta tags and schema
const baseURL = "https://demo.magic-portfolio.com";

const routes = {
  "/": true,
  "/about": true,
  "/work": true,
  "/blog": true,
  "/gallery": true,
  "/forum": true,
  "/forum/new-topic": true,
  "/forum/category/[id]": true,
  "/forum/topic/[id]": true,
  "/admin": true,
  "/admin/users": true,
  "/admin/forums": true,
  "/admin/logs": true,
  "/admin/system": true,
  "/admin/tools": true,
  "/admin/content": true,
  "/admin/seo": true,
  "/admin/settings": true,
  "/admin/forum": true,
  "/admin/forum/users": true,
  "/admin/forum/categories": true,
  "/admin/forum/moderation": true,
  "/admin/forum/settings": true,
  "/admin/forum/statistics": true,
  "/admin/forum/notifications": true,
  "/auth/login": true,
  "/auth/register": true,
  "/auth/reset-password": true,
  "/auth/activate": true,
  "/test-login": true,
  "/terms": true,
  "/profile": true,
  "/profile/[id]": true,
  // API роуты
  "/api/profile/[id]": true,
  "/api/forums": true,
  "/api/forums/[id]/topics": true,
  "/api/topics/[id]/posts": true,
  "/api/avatar/[id]": true,
  "/api/forum/categories": true,
  "/api/forum/categories/[id]": true,
  "/api/forum/topics": true,
  "/api/forum/topics/[id]": true,
  "/api/forum/posts": true,
  "/api/forum/posts/[id]": true,
  "/api/auth/login": true,
  "/api/auth/register": true,
  "/api/auth/reset-password": true,
  "/api/auth/activate": true,
  "/api/test-auth": true,
  "/api/debug": true,
  "/api/debug-auth": true,
  "/api/check-auth": true,
  "/api/test-simple": true,
  "/api/user/[id]/stats": true,
  "/api/admin/forum/stats": true,
  "/api/admin/forum/users": true,
  "/api/admin/forum/users/[id]/ban": true,
  "/api/admin/forum/users/[id]/group": true,
  "/api/admin/forum/users/[id]/update": true,
  "/api/admin/forum/categories": true,
  "/api/admin/forum/categories/[id]": true,
  "/api/admin/forum/categories/[id]/update": true,
  "/api/admin/forum/categories/create": true,
};

const display = {
  location: true,
  time: true,
  themeSwitcher: true
};

// Enable password protection on selected routes
// Set password in the .env file, refer to .env.example
const protectedRoutes = {
  "/work/automate-design-handovers-with-a-figma-to-code-pipeline": true,
};

// Import and set font for each variant
import { Geist } from "next/font/google";
import { Geist_Mono } from "next/font/google";

const heading = Geist({
  variable: "--font-heading",
  subsets: ["latin"],
  display: "swap",
});

const body = Geist({
  variable: "--font-body",
  subsets: ["latin"],
  display: "swap",
});

const label = Geist({
  variable: "--font-label",
  subsets: ["latin"],
  display: "swap",
});

const code = Geist_Mono({
  variable: "--font-code",
  subsets: ["latin"],
  display: "swap",
});

const fonts = {
  heading: heading,
  body: body,
  label: label,
  code: code,
};

// default customization applied to the HTML in the main layout.tsx
const style = {
  theme: "system", // dark | light | system
  neutral: "gray", // sand | gray | slate | custom
  brand: "cyan", // blue | indigo | violet | magenta | pink | red | orange | yellow | moss | green | emerald | aqua | cyan | custom
  accent: "red", // blue | indigo | violet | magenta | pink | red | orange | yellow | moss | green | emerald | aqua | cyan | custom
  solid: "contrast", // color | contrast
  solidStyle: "flat", // flat | plastic
  border: "playful", // rounded | playful | conservative
  surface: "translucent", // filled | translucent
  transition: "all", // all | micro | macro
  scaling: "100" // 90 | 95 | 100 | 105 | 110
};

const dataStyle = {
  variant: "gradient", // flat | gradient | outline
  mode: "categorical", // categorical | divergent | sequential
  height: 24, // default chart height
  axis: {
    stroke: "var(--neutral-alpha-weak)",
  },
  tick: {
    fill: "var(--neutral-on-background-weak)",
    fontSize: 11,
    line: false
  },
};

const effects = {
  mask: {
    cursor: false,
    x: 50,
    y: 0,
    radius: 100,
  },
  gradient: {
    display: false,
    opacity: 100,
    x: 50,
    y: 60,
    width: 100,
    height: 50,
    tilt: 0,
    colorStart: "accent-background-strong",
    colorEnd: "page-background",
  },
  dots: {
    display: true,
    opacity: 40,
    size: "2",
    color: "brand-background-strong",
  },
  grid: {
    display: false,
    opacity: 100,
    color: "neutral-alpha-medium",
    width: "0.25rem",
    height: "0.25rem",
  },
  lines: {
    display: false,
    opacity: 100,
    color: "neutral-alpha-weak",
    size: "16",
    thickness: 1,
    angle: 45,
  },
};

const mailchimp = {
  action: "https://url/subscribe/post?parameters",
  effects: {
    mask: {
      cursor: true,
      x: 50,
      y: 0,
      radius: 100,
    },
    gradient: {
      display: true,
      opacity: 90,
      x: 50,
      y: 0,
      width: 50,
      height: 50,
      tilt: 0,
      colorStart: "accent-background-strong",
      colorEnd: "static-transparent",
    },
    dots: {
      display: true,
      opacity: 20,
      size: "2",
      color: "brand-on-background-weak",
    },
    grid: {
      display: false,
      opacity: 100,
      color: "neutral-alpha-medium",
      width: "0.25rem",
      height: "0.25rem",
    },
    lines: {
      display: false,
      opacity: 100,
      color: "neutral-alpha-medium",
      size: "16",
      thickness: 1,
      angle: 90,
    },
  }
};

// default schema data
const schema = {
  logo: "",
  type: "Organization",
  name: "Once UI",
  description: home.description,
  email: "lorant@once-ui.com",
};

// social links
const sameAs = {
  threads: "https://www.threads.com/@once_ui",
  linkedin: "https://www.linkedin.com/company/once-ui/",
  discord: "https://discord.com/invite/5EyAQ4eNdS",
};

export { display, mailchimp, routes, protectedRoutes, baseURL, fonts, style, schema, sameAs, effects, dataStyle };
