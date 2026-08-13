package router

import "strings"

// segment representa um pedaço do caminho de uma rota já compilado:
// literal ("pacientes"), parâmetro nomeado (":id") ou coringa ("*").
type segment struct {
	literal    string
	paramName  string
	isParam    bool
	isWildcard bool
}

func parsePattern(pattern string) []segment {
	pattern = strings.Trim(pattern, "/")
	if pattern == "" {
		return []segment{}
	}

	parts := strings.Split(pattern, "/")
	segments := make([]segment, len(parts))
	for i, part := range parts {
		switch {
		case part == "*":
			segments[i] = segment{isWildcard: true}
		case strings.HasPrefix(part, ":"):
			segments[i] = segment{isParam: true, paramName: part[1:]}
		default:
			segments[i] = segment{literal: part}
		}
	}
	return segments
}

func splitPath(path string) []string {
	path = strings.Trim(path, "/")
	if path == "" {
		return []string{}
	}
	return strings.Split(path, "/")
}

// matchSegments compara os segmentos da requisição com o padrão compilado
// de uma rota, retornando os parâmetros nomeados capturados.
func matchSegments(pattern []segment, reqSegs []string) (map[string]string, bool) {
	var params map[string]string

	for i, seg := range pattern {
		if seg.isWildcard {
			if params == nil {
				params = make(map[string]string)
			}
			params["*"] = strings.Join(reqSegs[i:], "/")
			return params, true
		}

		if i >= len(reqSegs) {
			return nil, false
		}

		if seg.isParam {
			if params == nil {
				params = make(map[string]string)
			}
			params[seg.paramName] = reqSegs[i]
			continue
		}

		if seg.literal != reqSegs[i] {
			return nil, false
		}
	}

	if len(pattern) != len(reqSegs) {
		return nil, false
	}
	return params, true
}
