/**
* Theme: Adminto - Responsive Bootstrap 5 Admin Dashboard
* Author: Coderthemes
* Module/App: Form Wizard
*/

import {Wizard} from "./wizard";

new Wizard('#basicwizard');

new Wizard('#progressbarwizard', {
  progress: true
});

new Wizard('#validation-wizard', {
  validate: true
});