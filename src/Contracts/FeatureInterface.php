<?php
namespace FluentFlow\Contracts;

defined( 'ABSPATH' ) || exit;

interface FeatureInterface {

	public function get_id(): string;

	public function get_name(): string;

	public function get_description(): string;

	public function get_version(): string;

	public function get_icon(): string;

	public function init(): void;

	public function activate(): void;

	public function deactivate(): void;
}
