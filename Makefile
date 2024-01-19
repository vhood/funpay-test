-include .env

# Fallback

help:
	@make --help

# Image

environments:
	@cp .env.template .env

up:
	@docker compose up --build -d

down:
	@docker compose down --remove-orphans

# Testing

test:
	@docker compose run --rm php php test.php
