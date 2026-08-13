package router

import (
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestBasicMatchWithParam(t *testing.T) {
	rt := New()
	called := false
	rt.Get("/pacientes/:id", func(w http.ResponseWriter, r *http.Request) {
		called = true
		if got := Param(r, "id"); got != "42" {
			t.Errorf("param id = %q, want 42", got)
		}
		w.WriteHeader(http.StatusOK)
	})

	req := httptest.NewRequest(http.MethodGet, "/pacientes/42", nil)
	rec := httptest.NewRecorder()
	rt.ServeHTTP(rec, req)

	if !called {
		t.Fatal("handler não foi chamado")
	}
	if rec.Code != http.StatusOK {
		t.Fatalf("status = %d, want 200", rec.Code)
	}
}

func TestNotFound(t *testing.T) {
	rt := New()
	rt.Get("/pacientes", func(w http.ResponseWriter, r *http.Request) {})

	req := httptest.NewRequest(http.MethodGet, "/inexistente", nil)
	rec := httptest.NewRecorder()
	rt.ServeHTTP(rec, req)

	if rec.Code != http.StatusNotFound {
		t.Fatalf("status = %d, want 404", rec.Code)
	}
}

func TestMethodNotAllowed(t *testing.T) {
	rt := New()
	rt.Get("/pacientes", func(w http.ResponseWriter, r *http.Request) {})

	req := httptest.NewRequest(http.MethodPost, "/pacientes", nil)
	rec := httptest.NewRecorder()
	rt.ServeHTTP(rec, req)

	if rec.Code != http.StatusMethodNotAllowed {
		t.Fatalf("status = %d, want 405", rec.Code)
	}
}

func TestWildcard(t *testing.T) {
	rt := New()
	rt.Get("/arquivos/*", func(w http.ResponseWriter, r *http.Request) {
		if got := Wildcard(r); got != "fotos/2026/x.png" {
			t.Errorf("wildcard = %q", got)
		}
		w.WriteHeader(http.StatusOK)
	})

	req := httptest.NewRequest(http.MethodGet, "/arquivos/fotos/2026/x.png", nil)
	rec := httptest.NewRecorder()
	rt.ServeHTTP(rec, req)

	if rec.Code != http.StatusOK {
		t.Fatalf("status = %d, want 200", rec.Code)
	}
}

func TestGroupPrefix(t *testing.T) {
	rt := New()
	rt.Group("/api/v1", func(g *Router) {
		g.Get("/pacientes", func(w http.ResponseWriter, r *http.Request) {
			w.WriteHeader(http.StatusOK)
		})
	})

	req := httptest.NewRequest(http.MethodGet, "/api/v1/pacientes", nil)
	rec := httptest.NewRecorder()
	rt.ServeHTTP(rec, req)

	if rec.Code != http.StatusOK {
		t.Fatalf("status = %d, want 200", rec.Code)
	}

	req = httptest.NewRequest(http.MethodGet, "/pacientes", nil)
	rec = httptest.NewRecorder()
	rt.ServeHTTP(rec, req)
	if rec.Code != http.StatusNotFound {
		t.Fatalf("rota sem prefixo deveria dar 404, got %d", rec.Code)
	}
}

func TestLoadFromRouteMap(t *testing.T) {
	rt := New()
	rt.Load(Routes{
		"GET /pacientes":     func(w http.ResponseWriter, r *http.Request) { w.WriteHeader(http.StatusOK) },
		"POST /pacientes":    func(w http.ResponseWriter, r *http.Request) { w.WriteHeader(http.StatusCreated) },
		"GET /pacientes/:id": func(w http.ResponseWriter, r *http.Request) { w.WriteHeader(http.StatusOK) },
	})

	req := httptest.NewRequest(http.MethodPost, "/pacientes", nil)
	rec := httptest.NewRecorder()
	rt.ServeHTTP(rec, req)

	if rec.Code != http.StatusCreated {
		t.Fatalf("status = %d, want 201", rec.Code)
	}
}

func TestLoadInvalidKeyPanics(t *testing.T) {
	defer func() {
		if r := recover(); r == nil {
			t.Fatal("esperava panic para chave de rota inválida")
		}
	}()

	New().Load(Routes{
		"pacientes": func(w http.ResponseWriter, r *http.Request) {},
	})
}

func TestFirstMatchingRouteWins(t *testing.T) {
	rt := New()
	rt.Get("/pacientes/painel", func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusTeapot)
	})
	rt.Get("/pacientes/:id", func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	})

	req := httptest.NewRequest(http.MethodGet, "/pacientes/painel", nil)
	rec := httptest.NewRecorder()
	rt.ServeHTTP(rec, req)

	if rec.Code != http.StatusTeapot {
		t.Fatalf("rota literal deveria ter prioridade sobre :id, got %d", rec.Code)
	}
}
