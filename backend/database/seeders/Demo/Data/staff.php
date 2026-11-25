<?php

/**
 * Fictional staff covering every default role (SRS §3.1).
 * Roles are applied from Phase 1, when roles and permissions exist.
 * Emails use the reserved .test domain, so no message can reach a real inbox.
 */
return [
    ['key' => 'adunni', 'name' => 'Adunni Bakare', 'email' => 'adunni@adunni.test', 'role' => 'owner', 'skills' => []],
    ['key' => 'ngozi', 'name' => 'Ngozi Okafor', 'email' => 'ngozi@adunni.test', 'role' => 'supervisor', 'skills' => ['braids', 'wigs']],
    ['key' => 'kemi', 'name' => 'Kemi Adeyemi', 'email' => 'kemi@adunni.test', 'role' => 'cashier', 'skills' => []],
    ['key' => 'emeka', 'name' => 'Emeka Nwosu', 'email' => 'emeka@adunni.test', 'role' => 'cashier', 'skills' => []],
    ['key' => 'chiamaka', 'name' => 'Chiamaka Eze', 'email' => 'chiamaka@adunni.test', 'role' => 'employee', 'skills' => ['braids', 'wigs']],
    ['key' => 'funmilayo', 'name' => 'Funmilayo Ogunleye', 'email' => 'funmilayo@adunni.test', 'role' => 'employee', 'skills' => ['braids', 'hair-care']],
    ['key' => 'blessing', 'name' => 'Blessing Udoh', 'email' => 'blessing@adunni.test', 'role' => 'employee', 'skills' => ['braids']],
    ['key' => 'hauwa', 'name' => 'Hauwa Abdullahi', 'email' => 'hauwa@adunni.test', 'role' => 'employee', 'skills' => ['braids', 'hair-care']],
    ['key' => 'zainab', 'name' => 'Zainab Musa', 'email' => 'zainab@adunni.test', 'role' => 'employee', 'skills' => ['nails']],
    ['key' => 'efe', 'name' => 'Efe Omoregie', 'email' => 'efe@adunni.test', 'role' => 'employee', 'skills' => ['nails']],
    ['key' => 'tolani', 'name' => 'Tolani Johnson', 'email' => 'tolani@adunni.test', 'role' => 'employee', 'skills' => ['lashes', 'makeup']],
    ['key' => 'precious', 'name' => 'Precious Okon', 'email' => 'precious@adunni.test', 'role' => 'employee', 'skills' => ['braids', 'nails'], 'apprentice' => true],
];
