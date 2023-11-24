#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 80 - Перевозка с гидролифтом

Fact -> "гидролифт" | "гидролифтом" | "гидроборт" | "гидробортом";

S -> Fact interp(Category.Category_80);
