<?php

/**
 * PAC Completo IBGE - Gerador Web (Entry Point)
 * ==============================================
 * 
 * Este arquivo redireciona para api/index.php.
 * Tanto o servidor local (php -S localhost:8000)
 * quanto o Vercel usam a mesma lógica em api/index.php.
 * 
 * Uso: php -S localhost:8000
 */

// Redireciona para o arquivo principal em api/
require_once __DIR__ . '/api/index.php';
