
# Overview
The Student Alumni Portal was created to establish a connection between current students and the university's alumni. The project was inspired by the need to offer a platform through which students can have access to alumni to mentor them, give them career and career guidance, and participate in events.

Through this portal, the user can create an account (student/ alumni), make announcements and events, request mentorship, and handle community interactions by posting and commenting on events or posts posted by others. We were motivated to create a powerful relational database that facilitates such interactions using a normalized format, efficient queries, and minimum redundancy.



## Features

 Role-Based Authentication System
- Multi-tier Registration: Role based registration for three users.
- Session Management: Secure session handling with role-based access control

Dynamic Dashboard
- Student Dashboard: Personalized interface for current students
- Alumni Dashboard: Dedicated space for graduated students
- Admin Dashboard: Complete administrative control panel with system oversight

Content Management & Posting
- Multi-type Posts: Create and share general posts, news updates, career related posts and announcements
- Content Approval Workflow: All posts require admin approval before appearing in feed
- Post Moderation: Admin can approve or reject posts to maintain content quality

Interactive News Feed
- Real-time Updates: Dynamic feed displaying approved posts by admins from all users
- Content: Display of general posts, news, announcements, career related posts and events
- Role-based Content: Tailored content visibility based on user permissions

Event Management & Ticketing System
- Admin-only Event Creation: Exclusive event posting privileges for admins
- Integrated Ticketing: Built-in ticket purchasing system for event registration
- Event Categories: Support for various event types with detailed descriptions

Mentorship Request System
- Direct Alumni Connection: Students can request mentorship from specific alumni
- Structured Communication: Organized mentorship workflow and tracking

Administrative Controls
- Content Moderation: Complete control over post approval and rejection
- User Management: Monitor and manage all registered users across roles
- System Oversight: Full administrative privileges for platform maintenance

Security & Privacy
- Secure Authentication: Protected login system with encrypted passwords
- Role-based Permissions: Strict access control based on user roles
- Data Protection: Secure handling of user information and personal data


## Installation and deployment

Prerequisites
- XAMPP or similar local server environment
- Web browser (Chrome etc.)
- Code editor (VS Code etc.)
Steps
- Start XAMPP
- Start Apache and MySQL services
- Open phpMyAdmin (http://localhost/phpmyadmin)
- Database Setup : Import the student_alumni_portal.sql in your phpmyadmin
Configure Database Connection
- In DBconnect.php, these credentials are already included to run the project.Still check these credentials.
     ```bash
          $servername = "localhost";
          $username = "root";
          $password = "";
          $dbname = "student_alumni_portal";
     ```
After that run
- Place project folder in htdocs/

```bash
  localhost/student_alumni_portal/login.php
```


    
## Project Structure - EER diagram
<img width="952" height="395" alt="EER Diagram" src="https://github.com/user-attachments/assets/3b7f56e3-115e-46fc-87fb-eab63de5400b" />

## Project Structure - Schema
<img width="720" height="364" alt="Schema" src="https://github.com/user-attachments/assets/6f0cb949-d27d-4c99-b12a-5ff270bd5dfd" />

## Screenshots of all feature
Registration and login
- Role based registration
  <img width="1798" height="867" alt="registration" src="https://github.com/user-attachments/assets/5c3fb004-fad9-4a2e-af13-0430c0696027" />
  <img width="1882" height="934" alt="Registration_login" src="https://github.com/user-attachments/assets/5dcf8b16-9c42-4c01-ac74-f4ea5a633209" />

Home -> Dashboard of student, alumni, admin
- Student
  <img width="1819" height="834" alt="Student_dashboard_1st_screenshot" src="https://github.com/user-attachments/assets/91bfe0d1-21c5-45f7-bf64-05aa15655180" />
  <img width="1852" height="807" alt="student_dashboard_2nd_screenshot" src="https://github.com/user-attachments/assets/7160858e-5407-438e-ba3b-88e71ebc67b4" />

- Alumni
<img width="669" height="793" alt="alumni dashboard" src="https://github.com/user-attachments/assets/0ef5af5b-e4f4-42c5-a22a-44436235e5c7" />

- Admin
 <img width="966" height="858" alt="admin dashboard" src="https://github.com/user-attachments/assets/b05f8a6d-5e17-4b90-9b2b-e0ffdba77603" />

Feed
<img width="1746" height="869" alt="Community_feed" src="https://github.com/user-attachments/assets/0385e6a0-9098-4f10-8090-ba9042b822b4" />
<img width="1137" height="283" alt="Specific_post" src="https://github.com/user-attachments/assets/19de3783-ecd5-4851-bb68-d3b35aebe0a0" />
<img width="1002" height="323" alt="event_on_feed" src="https://github.com/user-attachments/assets/34ea2ad1-023b-4b48-b24d-eacc3c4daf29" />

Create post
<img width="1819" height="872" alt="Create_new_post" src="https://github.com/user-attachments/assets/fd6d0726-addd-4dac-b633-c7d953cf7fd4" />

Manage Post
<img width="1859" height="886" alt="manage_post" src="https://github.com/user-attachments/assets/6739448a-fc8d-47c5-ae40-72c11ef95db2" />

Create Event
<img width="1730" height="857" alt="manage_event_1st_screenshot" src="https://github.com/user-attachments/assets/d8bfea9b-9050-4f92-bc47-5085a94f344a" />
<img width="1760" height="746" alt="manage_event_2nd_screenshot" src="https://github.com/user-attachments/assets/14b5cc8a-4abb-4f23-8d63-cf13c2201320" />

Event tickets
<img width="1782" height="851" alt="Event_tickets" src="https://github.com/user-attachments/assets/24419f95-3984-4fb7-b04a-a422af46e2e6" />
Ticket looks
- Online look
  <img width="1866" height="858" alt="Online ticket look" src="https://github.com/user-attachments/assets/8f882616-9057-4d33-9654-0079368e9717" />
- Ticket look for printing
  <img width="1875" height="848" alt="Printed ticket look" src="https://github.com/user-attachments/assets/08627883-d678-4d5e-9022-c57f1cd738a9" />

Mentorship
<img width="1879" height="866" alt="Mentorship_overview" src="https://github.com/user-attachments/assets/f5c16ccb-7e6c-40e1-899f-bc5f5a114042" />

Find Mentor
<img width="1844" height="871" alt="find_mentors" src="https://github.com/user-attachments/assets/695a5cde-08db-40fe-8665-dbe5489eff1d" />

## Challenges faced
- Ensuring referential integrity across multiple relationships.
- Normalizing tables without losing important associations.
- Integrating frontend with backend queries.

## Future improvements
- Adding real-time chat between students and alumni.
- Notification system for new events and mentorship requests.
- Enhanced UI with modern frameworks (React/Angular).
- Email verification and two-step-authentication for safety purpose.
  
## Authors

- [NisatNisa](https://l.facebook.com/l.php?u=https%3A%2F%2Fgithub.com%2FNisatNisa%3Ffbclid%3DIwZXh0bgNhZW0CMTAAYnJpZBExNGlNY1dPNU9wSGRydHQ0aQEeiwws0qoSPoHuL61gjGldBgwmBo6mfdSkaseaDfinKTSDQmg3jHTOBewjTyk_aem_4DQ4iy0PacnlVAKgFx4xcQ&h=AT3M1JPglR0taKoms_r6EWv2yg148rtKZdtFf-nqHHTs7A0WehASJFpkIKEJ_9kDYtCJhyI1IOIH4F4_OwXD_mF_Hj4DlDPXpfEWiCx15SNTxsWON7N3A8_O9EAiHwbJLAwl0bSI5sWRfiz6Wz6cAA)
- [audree-saif](https://github.com/audree-saif?fbclid=IwY2xjawMtKyVleHRuA2FlbQIxMABicmlkETE0aU1jV081T3BIZHJ0dDRpAR7CgmOjkL03uFC7j6CzAZamn7aEgpAo8nZ4s3Cpr6X1J74pl65zIURfc6IDog_aem_6cX7ux4wil-zjrfWBNw7KQ)




