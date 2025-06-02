# Drupal Events Module – Dual Implementation

This repository contains a **Drupal project** showcasing two different approaches to building an **Events module**. The purpose of this project is to demonstrate flexibility in Drupal's architecture by implementing similar functionality using:

1. **Standard Drupal Content Tools (Content Types, Views, Taxonomy, etc.)**
2. **A Custom Module (Manual Handling of Everything – Models, Forms, Routing, Config, History)**

---

## 🧩 Module Overview

### ✅ 1. Standard Drupal Implementation

This approach leverages built-in Drupal features to create and manage events quickly using the UI and minimal custom code.

**Key Features:**
- **Content Type**: `Event` with fields like title, description, start date, and end date , taxonomy (category) ,images.
- **Views**: Configured to display a list of upcoming/past events.
- **Block Content**: Used for placing events list or calendar in predefined regions.
- **Taxonomy**: Categories used to classify events.
- **.module File**: Contains custom **form validation logic** for event submissions (e.g., end date must be greater than the start date ).

---

### 🛠 2. Custom Model-Based Implementation

This approach demonstrates a **custom-built module** where the entire event management system is created manually, offering full control and customizability.

**Key Features:**
- **Custom Database Tables**: Events,Event Images  ,Categoris,,Configurations, and Configuration Logs.
- **Custom Forms**: Manual forms built using Drupal Form API for:
  - Creating events
  - Setting up configurations
  - Logging changes to configurations
- **Config Management**:
  - Custom configuration entity or table to store module settings
  - History of edits (what data changed, when, and by whom)
- **Routing**:
  - `/categories` — List all categories
  - `/categories/add` — add new category
  - `/categories/id/delete` — delete category
  - `/categories/id/edit` — edit category
  - `/events` — List all events
  - `/events/add` — Form to add new events
  - `/events/{id}/view` — view  event
  - `/events/{id}/update` — Form to update existing events
  - `/events/{id}/delete` — Form to delete events
  - `/events/config` — Configuration form
  - `/events/config/log` — View the history of configuration changes
    //for content type events
   -/main-events2 -list all events created by content type
   -/node/add/main_events -add new content type of type main events
   -/admin/structure/taxonomy/manage/category/add - add new category taxonomy  
- **Validation**: Manual validation within the form classes
- **Access Control**: Users can access these routes after module installation and cache rebuild.

---

## 🚀 Getting Started

### Requirements
- Drupal 9 or 10 (recommended)
- PHP 8+
- Composer

### Installation Steps

1. Clone the repository:

   ```bash
   git clone [https://github.com/yourusername/drupal-events-module.git](https://github.com/samuelshany/Drupal-events.git)
   cd drupal-events
1.import  database from attached file link-event (3).sql (attached in the repo)
2.composer install

3.install 3 modules categories , events and main_events_block 
4. clear cash using  drush cr
