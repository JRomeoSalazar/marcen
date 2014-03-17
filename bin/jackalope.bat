@ECHO OFF
SET BIN_TARGET=%~dp0/../vendor/jackalope/jackalope-doctrine-dbal/bin/jackalope
php "%BIN_TARGET%" %*
