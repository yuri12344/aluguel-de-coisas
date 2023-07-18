<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public function toArray($request): array
	{
		$entity = [
			'id' => $this->id,
		];
		$columns = $this->getFillable();
		foreach ($columns as $column) {
			$entity[$column] = $this->{$column};
		}
		
		if (isset($this->distance)) {
			$entity['distance'] = $this->distance;
		}
		
		$embed = request()->filled('embed') ? explode(',', request()->get('embed')) : [];
		
		if (in_array('country', $embed)) {
			$entity['country'] = new CountryResource($this->whenLoaded('country'));
		}
		if (in_array('user', $embed)) {
			$entity['user'] = new UserResource($this->whenLoaded('user'));
		}
		if (in_array('category', $embed)) {
			$entity['category'] = new CategoryResource($this->whenLoaded('category'));
		}
		if (in_array('postType', $embed)) {
			$entity['postType'] = new PostTypeResource($this->whenLoaded('postType'));
		}
		if (in_array('city', $embed)) {
			$entity['city'] = new CityResource($this->whenLoaded('city'));
		}
		if (in_array('latestPayment', $embed)) {
			$entity['latestPayment'] = new PaymentResource($this->whenLoaded('latestPayment'));
		}
		if (in_array('savedByLoggedUser', $embed)) {
			$entity['savedByLoggedUser'] = UserResource::collection($this->whenLoaded('savedByLoggedUser'));
		}
		if (in_array('pictures', $embed)) {
			$entity['pictures'] = PictureResource::collection($this->whenLoaded('pictures'));
		}
		
		$entity['slug'] = $this->slug ?? null;
		$entity['url'] = $this->url ?? null;
		$entity['phone_intl'] = $this->phone_intl ?? null;
		$entity['created_at_formatted'] = $this->created_at_formatted ?? null;
		$entity['user_photo_url'] = $this->user_photo_url ?? null;
		$entity['country_flag_url'] = $this->country_flag_url ?? null;
		$entity['price_label'] = $this->price_label ?? t('price');
		$entity['price_formatted'] = $this->price_formatted ?? null;
		$entity['count_pictures'] = $this->count_pictures ?? 0;
		
		$defaultPicture = config('larapen.core.picture.default');
		$defaultPictureUrl = imgUrl($defaultPicture);
		$entity['picture'] = [
			'filename' => $this->picture ?? $defaultPicture,
			'url'      => [
				'full'   => $this->picture_url ?? $defaultPictureUrl,
				'small'  => $this->picture_url_small ?? $defaultPictureUrl,
				'medium' => $this->picture_url_medium ?? $defaultPictureUrl,
				'big'    => $this->picture_url_big ?? $defaultPictureUrl,
			],
		];
		
		// Reviews Plugin
		if (config('plugins.reviews.installed')) {
			$entity['rating_cache'] = $this->rating_cache ?? 0;
			$entity['rating_count'] = $this->rating_count ?? 0;
			// Warning: To prevent SQL queries in loop,
			// Never embed 'userRating' and 'countUserRatings' when collection will be returned
			if (in_array('userRating', $embed)) {
				$entity['p_user_rating'] = $this->userRating();
			}
			if (in_array('countUserRatings', $embed)) {
				$entity['p_count_user_ratings'] = $this->countUserRatings();
			}
		}
		
		return $entity;
	}
}
