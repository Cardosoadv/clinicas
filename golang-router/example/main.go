package main

import (
	"log"
	"net/http"

	"github.com/cardosoadv/clinicas/golang-router/router"
)

func main() {
	rt := router.New()
	rt.Load(Routes)

	log.Println("servindo em http://localhost:8080")
	log.Fatal(http.ListenAndServe(":8080", rt))
}
