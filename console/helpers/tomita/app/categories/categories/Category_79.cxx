#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 79 - Перевозка эвакуатором

Fact -> "эвакуатор" | "эвакуация" | "эвакуировать";

S -> Fact interp(Category.Category_79);