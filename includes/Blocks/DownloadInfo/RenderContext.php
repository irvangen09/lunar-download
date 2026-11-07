<?php

namespace LunarDownload\Blocks\DownloadInfo;

use LunarDownload\Services\LinkPolicy;

/**
 * Site-wide data that every entry on a page shares: the field definitions, the
 * link policy, and two display defaults. It is built once per request.
 */
class RenderContext {

	/**
	 * Field definitions keyed by slug.
	 *
	 * @var array[]
	 */
	private array $fields = array();

	private LinkPolicy $policy;

	private string $default_link_label;

	private string $date_format;

	/**
	 * @param array[]    $fields             Field definitions, archived ones included.
	 * @param LinkPolicy $policy             Decides rel and which links can be shown.
	 * @param string     $default_link_label Button text for a link without a label.
	 * @param string     $date_format        Format for date values.
	 */
	public function __construct( array $fields, LinkPolicy $policy, string $default_link_label, string $date_format ) {
		foreach ( $fields as $field ) {
			if ( is_array( $field ) && isset( $field['slug'] ) ) {
				$this->fields[ (string) $field['slug'] ] = $field;
			}
		}

		$this->policy             = $policy;
		$this->default_link_label = $default_link_label;
		$this->date_format        = $date_format;
	}

	public function field( string $slug ): ?array {
		return $this->fields[ $slug ] ?? null;
	}

	public function policy(): LinkPolicy {
		return $this->policy;
	}

	public function default_link_label(): string {
		return $this->default_link_label;
	}

	public function date_format(): string {
		return $this->date_format;
	}
}
