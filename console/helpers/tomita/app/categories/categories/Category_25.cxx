#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 25 - Малогабаритная перевозка

Fact -> "малогабаритный";

S -> Fact interp(Category.Category_25);