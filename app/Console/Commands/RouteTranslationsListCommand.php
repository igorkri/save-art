<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Mcamara\LaravelLocalization\Commands\RouteTranslationsListCommand as BaseRouteTranslationsListCommand;

#[Signature('route:trans:list
    {locale : The locale to list routes for}
    {--json : Output the route list as JSON}
    {--method= : Filter the routes by method}
    {--action= : Filter the routes by action}
    {--name= : Filter the routes by name}
    {--domain= : Filter the routes by domain}
    {--middleware= : Filter the routes by middleware}
    {--path= : Only show routes matching the given path pattern}
    {--except-path= : Do not display the routes matching the given path pattern}
    {--r|reverse : Reverse the ordering of the routes}
    {--sort=uri : The column to sort by}
    {--except-vendor : Do not display routes defined by vendor packages}
    {--only-vendor : Only display routes defined by vendor packages}')]
#[Description('List all registered routes for a specific locale')]
class RouteTranslationsListCommand extends BaseRouteTranslationsListCommand {}
