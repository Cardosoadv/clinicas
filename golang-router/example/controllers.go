package main

import (
	"encoding/json"
	"net/http"

	"github.com/cardosoadv/clinicas/golang-router/router"
)

type Paciente struct {
	ID   string `json:"id"`
	Nome string `json:"nome"`
}

func ListarPacientes(w http.ResponseWriter, r *http.Request) {
	pacientes := []Paciente{{ID: "1", Nome: "Rex"}, {ID: "2", Nome: "Mimi"}}
	json.NewEncoder(w).Encode(pacientes)
}

func ObterPaciente(w http.ResponseWriter, r *http.Request) {
	json.NewEncoder(w).Encode(Paciente{ID: router.Param(r, "id"), Nome: "Rex"})
}

func CriarPaciente(w http.ResponseWriter, r *http.Request) {
	w.WriteHeader(http.StatusCreated)
}

func AtualizarPaciente(w http.ResponseWriter, r *http.Request) {
	w.WriteHeader(http.StatusOK)
}

func RemoverPaciente(w http.ResponseWriter, r *http.Request) {
	w.WriteHeader(http.StatusNoContent)
}
